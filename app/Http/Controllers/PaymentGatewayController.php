<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentGatewayRequest;
use App\Models\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaymentGatewayController extends Controller
{
    public function store(StorePaymentGatewayRequest $request)
    {
        $validated = $request->validated();

        $gateway = Gateway::query()->create([
            'type'       => 'payment',
            'provider'   => $validated['provider'],
            'name'       => $validated['name'],
            'created_by' => $request->user()->id,
        ]);

        $gateway->syncCredentials($this->credentialFields($validated));

        return redirect()->route('settings.index')->with('success', 'Payment gateway added.');
    }

    public function update(StorePaymentGatewayRequest $request, Gateway $paymentGateway)
    {
        abort_unless($paymentGateway->type === 'payment', 404);

        $validated = $request->validated();

        $paymentGateway->update(['provider' => $validated['provider'], 'name' => $validated['name']]);
        $paymentGateway->syncCredentials($this->credentialFields($validated));

        return redirect()->route('settings.index')->with('success', 'Payment gateway updated.');
    }

    private function credentialFields(array $validated): array
    {
        return $validated['provider'] === 'mpesa'
            ? [
                'environment'     => $validated['environment'],
                'consumer_key'    => $validated['consumer_key'] ?? null,
                'consumer_secret' => $validated['consumer_secret'] ?? null,
                'shortcode'       => $validated['shortcode'],
                'passkey'         => $validated['passkey'] ?? null,
                'callback_url'    => $validated['callback_url'],
            ]
            : [
                'bank_name'      => $validated['bank_name'],
                'api_key'        => $validated['api_key'] ?? null,
                'api_secret'     => $validated['api_secret'] ?? null,
                'account_number' => $validated['account_number'],
                'endpoint_url'   => $validated['endpoint_url'],
            ];
    }

    public function activate(Gateway $paymentGateway): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('settings.manage'), 403);
        abort_unless($paymentGateway->type === 'payment', 404);

        DB::transaction(function () use ($paymentGateway) {
            Gateway::query()->where('type', 'payment')->where('id', '!=', $paymentGateway->id)->update(['is_active' => false]);
            $paymentGateway->update(['is_active' => true]);
        });

        return response()->json(['success' => true, 'message' => "{$paymentGateway->name} is now the active payment gateway."]);
    }

    public function destroy(Gateway $paymentGateway): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('settings.manage'), 403);
        abort_unless($paymentGateway->type === 'payment', 404);

        if ($paymentGateway->is_active) {
            return response()->json(['success' => false, 'message' => 'Cannot delete the active gateway. Activate another one first.'], 422);
        }

        $paymentGateway->delete();

        return response()->json(['success' => true, 'message' => 'Payment gateway deleted.']);
    }
}
