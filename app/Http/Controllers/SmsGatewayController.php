<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSmsGatewayRequest;
use App\Models\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
}
