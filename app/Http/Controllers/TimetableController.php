<?php

namespace App\Http\Controllers;

use App\Models\EducationLevel;
use App\Models\GradeLevel;
use App\Models\Stream;
use App\Models\TimeSlot;
use App\Models\TimeSlotGroup;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\TimetableSlotGroupSelection;
use App\Services\TimetableGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function index()
    {
        $timetables = Timetable::with('gradeLevels')->latest('created_at')->paginate(15);
        return view('timetables.index', compact('timetables'));
    }

    public function create()
    {
        $gradeLevels = GradeLevel::where('status', 'active')->with('educationLevel')->orderBy('sequence')->get();
        $educationLevels = EducationLevel::where('status', 'active')->orderBy('sequence')->get();
        $slotGroups = TimeSlotGroup::where('status', 'active')->orderBy('name')->get();

        return view('timetables.create', compact('gradeLevels', 'educationLevels', 'slotGroups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'academic_year' => ['required', 'string', 'max:9'],
            'term' => ['required', 'integer', 'in:1,2,3'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['string', 'exists:grade_levels,id'],
            'slot_groups' => ['array'], // education_level_id => time_slot_group_id
        ]);

        $coveredEducationLevelIds = GradeLevel::whereIn('id', $validated['grade_level_ids'])
            ->pluck('education_level_id')->unique();

        // Validate every education level actually covered by the chosen grades has a
        // group selected, before creating anything — a partial timetable with some
        // grades unschedulable is worse than rejecting the form up front.
        foreach ($coveredEducationLevelIds as $eduId) {
            if (empty($validated['slot_groups'][$eduId])) {
                $level = EducationLevel::find($eduId);
                return back()->withInput()->withErrors([
                    'slot_groups' => "Select a time slot group for {$level->name}.",
                ]);
            }
        }

        $timetable = DB::transaction(function () use ($validated, $coveredEducationLevelIds) {
            $timetable = Timetable::create([
                'name' => $validated['name'] ?: "Term {$validated['term']}, {$validated['academic_year']} Timetable",
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'],
                'status' => 'draft',
            ]);
            $timetable->gradeLevels()->attach($validated['grade_level_ids']);

            foreach ($coveredEducationLevelIds as $eduId) {
                TimetableSlotGroupSelection::create([
                    'timetable_id' => $timetable->id,
                    'education_level_id' => $eduId,
                    'time_slot_group_id' => $validated['slot_groups'][$eduId],
                ]);
            }

            return $timetable;
        });

        return redirect()->route('timetables.preview', $timetable->id)
            ->with('success', 'Timetable created. Click "Generate" to build the schedule.');
    }

    public function generate(Timetable $timetable, TimetableGenerationService $service)
    {
        abort_unless(request()->user()?->hasPermission('timetables.manage'), 403);
        abort_if($timetable->status !== 'draft', 400, 'Only draft timetables can be (re)generated.');

        $conflicts = $service->generate($timetable);

        return redirect()->route('timetables.preview', $timetable->id)
            ->with($conflicts ? 'warning' : 'success', $conflicts
                ? count($conflicts).' item(s) could not be placed — see the conflicts panel below.'
                : 'Timetable generated with no conflicts.');
    }

    public function preview(Timetable $timetable)
    {
        $timetable->load('gradeLevels');
        $streams = Stream::whereIn('grade_level_id', $timetable->gradeLevels->pluck('id'))
            ->join('grade_levels', 'grade_levels.id', '=', 'streams.grade_level_id')
            ->with('gradeLevel')
            ->orderBy('grade_levels.sequence')
            ->orderBy('streams.name')
            ->select('streams.*') // avoid column collisions from the join (both tables have 'id', 'created_at', etc.)
            ->get();

        $entries = TimetableEntry::where('timetable_id', $timetable->id)
            ->with(['timeSlot', 'learningArea', 'teacher', 'streams'])
            ->get();

        $slots = TimeSlot::where('type', 'main')->where('status', 'active')
            ->orderBy('day_of_week')->orderBy('sequence')->get()->groupBy('day_of_week');

        return view('timetables.preview', compact('timetable', 'streams', 'entries', 'slots'));
    }

    public function approve(Timetable $timetable)
    {
        abort_unless(request()->user()?->hasPermission('timetables.approve'), 403);
        abort_if($timetable->status !== 'draft', 400);

        $timetable->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);
        return back()->with('success', 'Timetable approved.');
    }

    public function publish(Timetable $timetable)
    {
        abort_unless(request()->user()?->hasPermission('timetables.publish'), 403);
        abort_if($timetable->status !== 'approved', 400, 'Only approved timetables can be published.');

        $timetable->update(['status' => 'published', 'published_at' => now(), 'published_by' => auth()->id()]);
        return back()->with('success', 'Timetable published.');
    }
}
