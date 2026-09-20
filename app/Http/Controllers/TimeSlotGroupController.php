<?php

namespace App\Http\Controllers;

use App\Models\TimeSlotGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimeSlotGroupController extends Controller
{
    public function index()
    {
        $groups = TimeSlotGroup::withCount('timeSlots')->orderBy('name')->get();
        return view('timetables.slot-groups.index', compact('groups'));
    }

    public function create() { return view('timetables.slot-groups.create'); }

    public function store(Request $request)
    {
        TimeSlotGroup::create($this->validated($request));
        return redirect()->route('timeslot-groups.index')->with('success', 'Time slot group created.');
    }

    public function edit(TimeSlotGroup $timeslotGroup)
    {
        return view('timetables.slot-groups.edit', ['group' => $timeslotGroup]);
    }

    public function update(Request $request, TimeSlotGroup $timeslotGroup)
    {
        $timeslotGroup->update($this->validated($request, $timeslotGroup->id));
        return redirect()->route('timeslot-groups.index')->with('success', 'Time slot group updated.');
    }

    public function destroy(TimeSlotGroup $timeslotGroup): JsonResponse
    {
        if ($timeslotGroup->timeSlots()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete a group that still has time slots.'], 422);
        }
        $timeslotGroup->delete();
        return response()->json(['success' => true, 'message' => 'Group deleted.']);
    }

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', Rule::unique('time_slot_groups', 'code')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
