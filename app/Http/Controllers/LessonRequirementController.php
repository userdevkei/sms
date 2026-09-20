<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\LearningArea;
use App\Models\LessonRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LessonRequirementController extends Controller
{
    public function index()
    {
        $gradeLevels = GradeLevel::where('status', 'active')
            ->withCount(['learningAreas' => fn ($q) => $q])
            ->with('educationLevel')
            ->orderBy('sequence')->get();

        $configuredCounts = LessonRequirement::query()
            ->select('grade_level_id', DB::raw('count(*) as c'))
            ->groupBy('grade_level_id')->pluck('c', 'grade_level_id');

        return view('curriculum.lesson-requirements.index', compact('gradeLevels', 'configuredCounts'));
    }

    public function edit(GradeLevel $gradeLevel)
    {
        abort_unless(request()->user()?->hasPermission('curriculum.manage'), 403);

        if ($gradeLevel->isSeniorSecondary()){
            $learningAreas = LearningArea::whereHas('pathways')->orderBy('name')->get();
        }else{
            $learningAreas = $gradeLevel->learningAreas()->orderBy('name')->get();

        }

        $existing = LessonRequirement::where('grade_level_id', $gradeLevel->id)
            ->get()->keyBy('learning_area_id');

        return view('curriculum.lesson-requirements.edit', compact('gradeLevel', 'learningAreas', 'existing'));
    }

    public function update(Request $request, GradeLevel $gradeLevel)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $request->validate([
            'requirements' => ['required', 'array'],
            'requirements.*.learning_area_id'       => ['required', 'string', 'exists:learning_areas,id'],
            'requirements.*.lessons_per_week'        => ['required', 'integer', 'min:0', 'max:15'],
            'requirements.*.double_lessons_per_week' => ['required', 'integer', 'min:0', 'max:15'],
            'requirements.*.enabled'                 => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $gradeLevel) {
            foreach ($validated['requirements'] as $row) {
                $enabled = $row['enabled'] ?? false;

                if (! $enabled) {
                    // Unchecking a subject removes its requirement rather than
                    // leaving a stale "0 lessons/week" row the generator would
                    // otherwise silently treat as "this subject gets nothing."
                    LessonRequirement::where('grade_level_id', $gradeLevel->id)
                        ->where('learning_area_id', $row['learning_area_id'])
                        ->delete();
                    continue;
                }

                if ($row['double_lessons_per_week'] * 2 > $row['lessons_per_week']) {
                    throw ValidationException::withMessages([
                        'requirements' => "Double lessons can't exceed total lessons/week for one of the subjects.",
                    ]);
                }

                LessonRequirement::updateOrCreate(
                    ['grade_level_id' => $gradeLevel->id, 'learning_area_id' => $row['learning_area_id']],
                    [
                        'lessons_per_week' => $row['lessons_per_week'],
                        'double_lessons_per_week' => $row['double_lessons_per_week'],
                        'status' => 'active',
                    ]
                );
            }
        });

        return redirect()->route('curriculum.lesson-requirements.index')
            ->with('success', "Lesson requirements updated for {$gradeLevel->name}.");
    }
}
