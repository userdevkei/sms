<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSmsGatewayRequest;
use App\Models\Gateway;
use App\Services\Communication\GatewayResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SmsGatewayController extends Controller
{
    public function store(StoreSmsGatewayRequest $request)
    {
        $validated = $request->validated();

        $gateway = Gateway::query()->create([
            'type'       => 'sms',
            'provider'   => $validated['provider'],
            'name'       => $validated['name'],
            'created_by' => $request->user()->id,
        ]);

        $gateway->syncCredentials($this->credentialFields($validated));

        return redirect()->route('settings.index')->with('success', 'SMS gateway added.');
    }

    public function update(StoreSmsGatewayRequest $request, Gateway $smsGateway)
    {
        abort_unless($smsGateway->type === 'sms', 404);

        $validated = $request->validated();

        $smsGateway->update(['provider' => $validated['provider'], 'name' => $validated['name']]);
        $smsGateway->syncCredentials($this->credentialFields($validated));

        return redirect()->route('settings.index')->with('success', 'SMS gateway updated.');
    }

    private function credentialFields(array $validated): array
    {
        return $validated['provider'] === 'africas_talking'
            ? ['username' => $validated['username'], 'api_key' => $validated['api_key'] ?? null, 'sender_id' => $validated['sender_id'] ?? null]
            : ['endpoint_url' => $validated['endpoint_url'], 'api_key' => $validated['api_key'] ?? null];
    }

    /** Only one SMS gateway is active app-wide at a time. */
    public function activate(Gateway $smsGateway): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('settings.manage'), 403);
        abort_unless($smsGateway->type === 'sms', 404);

        DB::transaction(function () use ($smsGateway) {
            Gateway::query()->where('type', 'sms')->where('id', '!=', $smsGateway->id)->update(['is_active' => false]);
            $smsGateway->update(['is_active' => true]);
        });

        return response()->json(['success' => true, 'message' => "{$smsGateway->name} is now the active SMS gateway."]);
    }

    public function destroy(Gateway $smsGateway): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('settings.manage'), 403);
        abort_unless($smsGateway->type === 'sms', 404);

        if ($smsGateway->is_active) {
            return response()->json(['success' => false, 'message' => 'Cannot delete the active gateway. Activate another one first.'], 422);
        }

        $smsGateway->delete(); // credentials cascade-delete via FK

        return response()->json(['success' => true, 'message' => 'SMS gateway deleted.']);
    }

    public function test(Request $request, Gateway $smsGateway): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('settings.manage'), 403);
        abort_unless($smsGateway->type === 'sms', 404);

        try {
            $request->validate(['destination' => ['required', 'string', 'min:9', 'max:15']]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->validator->errors()->first('destination')], 422);
        }

        $cfg     = $smsGateway->config();
        $message = $request->input('message') ?: "This is a test message from {$smsGateway->name}. Your SMS gateway is configured correctly.";

        try {
            $result = match ($smsGateway->provider) {
                'africas_talking' => $this->sendViaAfricasTalking($cfg, $request->input('destination'), $message),
                default           => $this->sendViaGenericEndpoint($cfg, $request->input('destination'), $message),
            };

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success']
                    ? "Test SMS sent to {$request->input('destination')}."
                    : ('Send failed: ' . ($result['error'] ?? 'Unknown error')),
            ], $result['success'] ? 200 : 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Send failed: ' . $e->getMessage(),
            ], 422);
        }
    }
    private function sendViaAfricasTalking(array $cfg, string $destination, string $message): array
    {
        $response = Http::withHeaders([
            'apiKey'       => $cfg['api_key'],
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->post('https://api.africastalking.com/version1/messaging/bulk', [
                'username' => $cfg['username'],
                'to'       => $destination,
                'message'  => $message,
                'from'     => $cfg['sender_id'] ?? null,
            ]);

        Log::info('AT test response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        $data = $response->json();
        $recipient = $data['SMSMessageData']['Recipients'][0] ?? null;

        return [
            'success' => $response->successful() && ($recipient['status'] ?? null) === 'Success',
            'error'   => $recipient['status'] ?? ($data['SMSMessageData']['Message'] ?? 'Unexpected response'),
        ];
    }

    private function sendViaGenericEndpoint(array $cfg, string $destination, string $message): array
    {
        $response = Http::withHeaders(['Authorization' => "Bearer {$cfg['api_key']}"])
            ->post($cfg['endpoint_url'], [
                'to'      => $destination,
                'message' => $message,
            ]);

        return [
            'success' => $response->successful(),
            'error'   => $response->successful() ? null : $response->body(),
        ];
    }
}
