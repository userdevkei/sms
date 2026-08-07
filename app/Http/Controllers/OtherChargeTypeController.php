<?php

namespace App\Http\Controllers;

use App\Models\OtherChargeType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtherChargeTypeController extends Controller
{
    public function index()
    {
        return view('finance.other-charge-types.index', ['types' => OtherChargeType::query()->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasPermission('other_charges.manage'), 403);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150', 'unique:other_charge_types,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:active,inactive'],
        ]);

        OtherChargeType::query()->create($validated);

        return redirect()->route('finance.other-charge-types.index')->with('success', 'Charge type added.');
    }

    public function update(Request $request, OtherChargeType $otherChargeType)
    {
        abort_unless($request->user()?->hasPermission('other_charges.manage'), 403);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150', \Illuminate\Validation\Rule::unique('other_charge_types', 'name')->ignore($otherChargeType->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:active,inactive'],
        ]);

        $otherChargeType->update($validated);

        return redirect()->route('finance.other-charge-types.index')->with('success', 'Charge type updated.');
    }

    public function destroy(OtherChargeType $otherChargeType): JsonResponse
    {
        abort_unless(request()->user()?->hasPermission('other_charges.manage'), 403);

        if ($otherChargeType->otherCharges()->exists() ?? \App\Models\OtherCharge::where('other_charge_type_id', $otherChargeType->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'This type has charges recorded against it.'], 422);
        }

        $otherChargeType->delete();

        return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
    }
}
