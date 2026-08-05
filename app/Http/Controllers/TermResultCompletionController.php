<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\StudentEnrollment;
use App\Models\TermResultCompletion;
use Illuminate\Http\Request;

class TermResultCompletionController extends Controller
{
    public function edit(GradeLevel $gradeLevel)
    {
        $enrollments = StudentEnrollment::query()
            ->where('grade_level_id', $gradeLevel->id)
            ->where('status', 'active')
            ->with('student')
            ->get()
            ->sortBy(fn ($e) => $e->student->full_name)
            ->values();

        $completions = TermResultCompletion::query()
            ->whereIn('user_id', $enrollments->pluck('user_id'))
            ->get()
            ->groupBy('user_id');

        return view('curriculum.term-results.edit', compact('gradeLevel', 'enrollments', 'completions'));
    }

    public function update(Request $request, GradeLevel $gradeLevel)
    {
        abort_unless($request->user()?->hasPermission('curriculum.manage'), 403);

        $validated = $request->validate([
            'completions'                 => ['nullable', 'array'],
            'completions.*.user_id'       => ['required', 'string', 'exists:users,id'],
            'completions.*.academic_year' => ['required', 'string', 'max:9'],
            'completions.*.terms'         => ['nullable', 'array'],
            'completions.*.terms.*'       => ['integer', 'in:1,2,3'],
        ]);

        foreach ($validated['completions'] ?? [] as $entry) {
            foreach ([1, 2, 3] as $term) {
                $shouldBeComplete = in_array($term, $entry['terms'] ?? []);

                TermResultCompletion::query()->updateOrCreate(
                    ['user_id' => $entry['user_id'], 'academic_year' => $entry['academic_year'], 'term_number' => $term],
                    ['completed_at' => $shouldBeComplete ? now() : null, 'recorded_by' => $request->user()->id]
                );
            }
        }

        return redirect()->route('curriculum.progression.show', $gradeLevel->id)->with('success', 'Term result completion updated.');
    }
}
