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
        return match ($validated['provider']) {
            'mpesa' => [
                'environment'     => $validated['environment'],
                'consumer_key'    => $validated['consumer_key'] ?? null,
                'consumer_secret' => $validated['consumer_secret'] ?? null,
                'shortcode'       => $validated['shortcode'],
                'passkey'         => $validated['passkey'] ?? null,
            ],
            'equity' => [
                'environment'     => $validated['environment'],
                'account_number'  => $validated['account_number'],
                'ipn_username'    => $validated['ipn_username'],
                'ipn_password'    => $validated['ipn_password'] ?? null,
            ],
            'kcb' => [
                'environment'     => $validated['environment'],
                'account_number'  => $validated['account_number'],
                'kcb_public_key'  => $validated['kcb_public_key'] ?? null,
                'consumer_key'    => $validated['consumer_key'] ?? null,
                'consumer_secret' => $validated['consumer_secret'] ?? null,
            ],
            'coop' => [
                'environment'     => $validated['environment'],
                'account_number'  => $validated['account_number'],
                'api_key'         => $validated['api_key'] ?? null,
                'ipn_key'         => $validated['ipn_key'] ?? null,
            ],
        };
    }

    public function activate(Gateway $paymentGateway): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('settings.manage'), 403);
        abort_unless($paymentGateway->type === 'payment', 404);

        DB::transaction(function () use ($paymentGateway) {
            Gateway::query()->where('type', 'payment')
                ->where('provider', '=', $paymentGateway->provider)
                ->update(['is_active' => false]);
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
