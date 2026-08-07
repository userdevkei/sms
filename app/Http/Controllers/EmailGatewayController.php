<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailGatewayRequest;
use App\Models\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EmailGatewayController extends Controller
{
    public function store(StoreEmailGatewayRequest $request)
    {
        $validated = $request->validated();

        $gateway = Gateway::query()->create([
            'type'       => 'email',
            'provider'   => 'smtp',
            'name'       => $validated['name'],
            'created_by' => $request->user()->id,
        ]);

        $gateway->syncCredentials($this->credentialFields($validated));

        return redirect()->route('settings.index')->with('success', 'Email gateway added.');
    }

    public function update(StoreEmailGatewayRequest $request, Gateway $emailGateway)
    {
        abort_unless($emailGateway->type === 'email', 404);

        $validated = $request->validated();

        $emailGateway->update(['name' => $validated['name']]);
        $emailGateway->syncCredentials($this->credentialFields($validated));

        return redirect()->route('settings.index')->with('success', 'Email gateway updated.');
    }

    private function credentialFields(array $validated): array
    {
        return [
            'host'         => $validated['host'],
            'port'         => (string) $validated['port'],
            'username'     => $validated['username'],
            'password'     => $validated['password'] ?? null,
            'encryption'   => $validated['encryption'],
            'from_address' => $validated['from_address'],
            'from_name'    => $validated['from_name'],
        ];
    }

    public function activate(Gateway $emailGateway): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('settings.manage'), 403);
        abort_unless($emailGateway->type === 'email', 404);

        DB::transaction(function () use ($emailGateway) {
            Gateway::query()->where('type', 'email')->where('id', '!=', $emailGateway->id)->update(['is_active' => false]);
            $emailGateway->update(['is_active' => true]);
        });

        return response()->json(['success' => true, 'message' => "{$emailGateway->name} is now the active email gateway."]);
    }

    public function destroy(Gateway $emailGateway): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('settings.manage'), 403);
        abort_unless($emailGateway->type === 'email', 404);

        if ($emailGateway->is_active) {
            return response()->json(['success' => false, 'message' => 'Cannot delete the active gateway. Activate another one first.'], 422);
        }

        $emailGateway->delete();

        return response()->json(['success' => true, 'message' => 'Email gateway deleted.']);
    }
}
