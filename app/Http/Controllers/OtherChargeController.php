<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOtherChargeRequest;
use App\Models\GradeLevel;
use App\Models\OtherCharge;
use App\Models\OtherChargeType;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class OtherChargeController extends Controller
{
    public function index()
    {
        $charges = OtherCharge::query()->with(['type', 'gradeLevel', 'stream', 'student'])->latest()->get();

        return view('finance.other-charges.index', compact('charges'));
    }

    public function create()
    {
        $types = OtherChargeType::query()->where('status', 'active')->orderBy('name')->get();
        $gradeLevels = GradeLevel::query()->where('status', 'active')->orderBy('sequence')->get();
        $streams = Stream::query()->where('status', 'active')->with('gradeLevel')->orderBy('name')->get();
        $students = User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'student'))->orderBy('first_name')->get(['id', 'first_name', 'middle_name', 'last_name', 'userID']);

        return view('finance.other-charges.create', compact('types', 'gradeLevels', 'streams', 'students'));
    }

    public function store(StoreOtherChargeRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'active';

        // Only the field matching the chosen scope should actually be saved —
        // clear the other two so a stale grade_level_id doesn't silently
        // widen a charge that was meant for one student only.
        foreach (['user_id', 'stream_id', 'grade_level_id'] as $field) {
            if ($validated['scope'] !== match ($field) { 'user_id' => 'student', 'stream_id' => 'stream', 'grade_level_id' => 'grade_level' }) {
                $validated[$field] = null;
            }
        }
        unset($validated['scope']);

        $charge = OtherCharge::query()->create($validated);

        return redirect()->route('finance.other-charges.index')
            ->with('success', "Charge added \u2014 affects {$charge->affectedStudentIds()->count()} student(s) once invoiced.");
    }

    public function destroy(OtherCharge $otherCharge): JsonResponse
    {
        if ($otherCharge->status === 'invoiced') {
            return response()->json(['success' => false, 'message' => 'This charge has already been invoiced and cannot be deleted.'], 422);
        }

        $otherCharge->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Charge cancelled.']);
    }
}
