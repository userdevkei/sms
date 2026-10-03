<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWhatsappGatewayRequest;
use App\Models\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class WhatsappGatewayController extends Controller
{
    public function store(StoreWhatsappGatewayRequest $request)
    {
        $validated = $request->validated();

        $gateway = Gateway::query()->create([
            'type'       => 'whatsapp',
            'provider'   => $validated['provider'],
            'name'       => $validated['name'],
            'created_by' => $request->user()->id,
        ]);

        $gateway->syncCredentials($this->credentialFields($validated));

        return redirect()->route('settings.index')->with('success', 'WhatsApp gateway added.');
    }

    public function update(StoreWhatsappGatewayRequest $request, Gateway $whatsappGateway)
    {
        abort_unless($whatsappGateway->type === 'whatsapp', 404);

        $validated = $request->validated();

        $whatsappGateway->update(['provider' => $validated['provider'], 'name' => $validated['name']]);
        $whatsappGateway->syncCredentials($this->credentialFields($validated));

        return redirect()->route('settings.index')->with('success', 'WhatsApp gateway updated.');
    }

    /**
     * Send a one-off test WhatsApp message through this gateway's configured
     * provider, without touching is_active or any queued-message pipeline.
     */
    public function test(Request $request, Gateway $whatsappGateway): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('settings.manage'), 403);
        abort_unless($whatsappGateway->type === 'whatsapp', 404);

        $validated = $request->validate([
            'destination' => ['required', 'string', 'max:32'],
            'message'     => ['nullable', 'string', 'max:1000'],
        ]);

        $message = $validated['message'] ?: "This is a test message from {$whatsappGateway->name}.";
        $config = $whatsappGateway->config();
        $note = null;

        try {
            $note = match ($whatsappGateway->provider) {
                'whatsapp_cloud' => $this->sendViaWhatsappCloud($config, $validated['destination'], $message),
                'twilio' => $this->sendViaTwilio($config, $validated['destination'], $message),
                default => $this->sendViaCustom($config, $validated['destination'], $message), // custom
            };
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Test message failed: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $note ?? "Test WhatsApp message sent to {$validated['destination']}.",
        ]);
    }

    /**
     * Returns null on a normal send, or an explanatory note if the free-form
     * message had to fall back to a template.
     */

    private function sendViaWhatsappCloud(array $config, string $destination, string $message): ?string
    {
        $phoneNumberId = $config['phone_number_id'] ?? null;
        $accessToken = $config['access_token'] ?? null;

        Log::info($phoneNumberId, $accessToken);

        abort_if(! $phoneNumberId || ! $accessToken, 422, 'WhatsApp Cloud gateway is missing its phone number ID or access token.');

        $to = preg_replace('/\D+/', '', $destination);

        // Business-initiated messages (which is nearly everything this app sends
        // — fee reminders, notices, alerts) must go through an approved template
        // regardless of conversation window status. Free-form "text" only works
        // when the recipient messaged you first within the last 24 hours, and
        // WhatsApp can accept a free-form send with a 200 OK and still fail it
        // asynchronously later via webhook — so there's no reliable synchronous
        // signal to fall back on. Template is the only dependable path.
        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v20.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                'template'          => [
                    'name'     => 'hello_world',
                    'language' => ['code' => 'en_US'],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message') ?? 'WhatsApp Cloud API request failed.');
        }

        return "Credentials work. Sent via the hello_world template rather than your typed message — WhatsApp requires business-initiated messages like this to use an approved template, since the recipient hasn't messaged this number first.";
    }

    private function sendViaTwilio(array $config, string $destination, string $message): ?string
    {
        $accountSid = $config['account_sid'] ?? null;
        $authToken = $config['auth_token'] ?? null;
        $from = $config['from'] ?? null;

        abort_if(! $accountSid || ! $authToken || ! $from, 422, 'Twilio gateway is missing its Account SID, Auth Token, or From number.');

        $to = str_starts_with($destination, 'whatsapp:') ? $destination : 'whatsapp:' . $destination;
        $fromNumber = str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:' . $from;

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'To'   => $to,
                'From' => $fromNumber,
                'Body' => $message,
            ]);

        if ($response->failed()) {
            throw new RuntimeException($response->json('message') ?? 'Twilio API request failed.');
        }

        return null;
    }

    private function sendViaCustom(array $config, string $destination, string $message): ?string
    {
        $endpointUrl = $config['endpoint_url'] ?? null;
        $apiKey = $config['api_key'] ?? null;

        abort_if(! $endpointUrl, 422, 'Custom gateway is missing its endpoint URL.');

        $request = Http::acceptJson();
        if ($apiKey) {
            $request = $request->withToken($apiKey);
        }

        $response = $request->post($endpointUrl, [
            'to'      => $destination,
            'message' => $message,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Custom endpoint returned HTTP ' . $response->status() . '.');
        }

        return null;
    }

    private function credentialFields(array $validated): array
    {
        return match ($validated['provider']) {
            'whatsapp_cloud' => [
                'phone_number_id'     => $validated['phone_number_id'],
                'business_account_id' => $validated['business_account_id'] ?? null,
                'access_token'        => $validated['access_token'] ?? null,
                'verify_token'        => $validated['verify_token'] ?? null,
            ],
            'twilio' => [
                'account_sid' => $validated['account_sid'],
                'auth_token'  => $validated['auth_token'] ?? null,
                'from'        => $validated['from'],
            ],
            default => [ // custom
                'endpoint_url' => $validated['endpoint_url'],
                'api_key'      => $validated['api_key'] ?? null,
            ],
        };
    }

    /** Only one WhatsApp gateway is active app-wide at a time. */
    public function activate(Gateway $whatsappGateway): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('settings.manage'), 403);
        abort_unless($whatsappGateway->type === 'whatsapp', 404);

        DB::transaction(function () use ($whatsappGateway) {
            Gateway::query()->where('type', 'whatsapp')->where('id', '!=', $whatsappGateway->id)->update(['is_active' => false]);
            $whatsappGateway->update(['is_active' => true]);
        });

        return response()->json(['success' => true, 'message' => "{$whatsappGateway->name} is now the active WhatsApp gateway."]);
    }

    public function destroy(Gateway $whatsappGateway): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('settings.manage'), 403);
        abort_unless($whatsappGateway->type === 'whatsapp', 404);

        if ($whatsappGateway->is_active) {
            return response()->json(['success' => false, 'message' => 'Cannot delete the active gateway. Activate another one first.'], 422);
        }

        $whatsappGateway->delete(); // credentials cascade-delete via FK

        return response()->json(['success' => true, 'message' => 'WhatsApp gateway deleted.']);
    }
}
