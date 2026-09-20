<?php

namespace App\Http\Controllers;

use App\Models\TimeSlot;
use App\Models\TimeSlotGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeSlotController extends Controller
{
    public function index(Request $request)
    {
        $groupId = $request->query('group');
        if (! $groupId) {
            return redirect()->route('timeslot-groups.index')
                ->with('error', 'Select a time slot group to manage its slots.');
        }

        $group = TimeSlotGroup::findOrFail($groupId);
        $slots = TimeSlot::where('time_slot_group_id', $groupId)
            ->orderBy('day_of_week')->orderBy('sequence')->get()->groupBy('day_of_week');

        return view('timetables.slots.index', compact('group', 'slots'));
    }

    public function create(Request $request)
    {
        $group = TimeSlotGroup::findOrFail($request->query('group'));
        return view('timetables.slots.create', compact('group'));
    }

    public function store(Request $request)
    {
        TimeSlot::create($this->validated($request));
        return redirect()->route('timeslots.index', ['group' => $request->input('time_slot_group_id')])
            ->with('success', 'Time slot added.');
    }

    public function edit(TimeSlot $timeslot)
    {
        $group = $timeslot->group;
        return view('timetables.slots.edit', compact('timeslot', 'group'));
    }

    public function update(Request $request, TimeSlot $timeslot)
    {

        dd($this->validated($request, $timeslot->id));

return $timeslot->update($this->validated($request, $timeslot->id));
        return redirect()->route('timeslots.index', ['group' => $timeslot->time_slot_group_id])
            ->with('success', 'Time slot updated.');
    }

    public function destroy(TimeSlot $timeslot): JsonResponse
    {
        $timeslot->delete();
        return response()->json(['success' => true, 'message' => 'Time slot deleted.']);
    }

    public function bulkCreate(Request $request)
    {
        $group = TimeSlotGroup::findOrFail($request->query('group'));
        return view('timetables.slots.bulk-create', compact('group'));
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'time_slot_group_id'   => ['required', 'string', 'exists:time_slot_groups,id'],
            'days'                 => ['required', 'array', 'min:1'],
            'days.*'               => ['integer', 'between:1,7'],
            'rows'                 => ['required', 'array', 'min:1'],
            'rows.*.start_time'    => ['required', 'date_format:H:i'],
            'rows.*.end_time'      => ['required', 'date_format:H:i', 'after:rows.*.start_time'],
            // A break row's <select> is disabled client-side, so it never reaches
            // the request at all — only require type when the row isn't a break.
//            'rows.*.type'          => ['required_unless:rows.*.is_break,1', 'nullable', 'in:main,remedial'],
            'rows.*.label'         => ['nullable', 'string', 'max:100'],
            'rows.*.is_break'      => ['nullable', 'boolean'],
            'skip_existing'        => ['nullable', 'boolean'],
        ]);

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($validated, &$created, &$skipped) {
            foreach ($validated['days'] as $day) {
                foreach ($validated['rows'] as $sequence => $row) {
                    $exists = TimeSlot::where('time_slot_group_id', $validated['time_slot_group_id'])
                        ->where('day_of_week', $day)
                        ->where('start_time', $row['start_time'])
                        ->where('end_time', $row['end_time'])
                        ->exists();

                    if ($exists && ($validated['skip_existing'] ?? false)) {
                        $skipped++;
                        continue;
                    }

                    TimeSlot::create([
                        'time_slot_group_id' => $validated['time_slot_group_id'],
                        'day_of_week' => $day,
                        'start_time'  => $row['start_time'],
                        'end_time'    => $row['end_time'],
                        // A break row has no submitted type — treat it as 'main' rather
                        // than letting a missing key become a null column value.
                        'type'        => $row['type'] ?? 'main',
                        'label'       => $row['label'] ?: null,
                        'sequence'    => $sequence + 1,
                        'is_break'    => $row['is_break'] ?? false,
                        'status'      => 'active',
                    ]);
                    $created++;
                }
            }
        });

        return redirect()->route('timeslots.index', ['group' => $validated['time_slot_group_id']])
            ->with('success', "{$created} time slot(s) created" . ($skipped ? ", {$skipped} skipped as duplicates" : '') . '.');
    }

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'time_slot_group_id' => ['required', 'string', 'exists:time_slot_groups,id'],
//            'day_of_week' => ['required', 'integer', 'between:1,7'],
//            'start_time' => ['required', 'date_format:H:i'],
//            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
//            'type' => ['required', 'in:main,remedial'],
//            'label' => ['nullable', 'string', 'max:100'],
//            'sequence' => ['required', 'integer', 'min:1'],
//            'is_break' => ['nullable', 'boolean'],
//            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
