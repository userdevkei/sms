<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePathwayClassificationRequest;
use App\Models\GradeLevel;
use App\Models\Pathway;
use App\Models\ProgressionException;
use App\Models\StudentEnrollment;
use App\Services\Curriculum\ProgressionEligibilityService;
use Illuminate\Support\Facades\DB;

class PathwayClassificationController extends Controller
{
    public function __construct(private ProgressionEligibilityService $eligibility) {}

    public function create(GradeLevel $gradeLevel)
    {
        $nextGradeLevel = $gradeLevel->nextGradeLevel();

        abort_unless(
            $nextGradeLevel && $nextGradeLevel->isSeniorSecondaryEntryGrade() && ! $gradeLevel->isSeniorSecondary(),
            422,
            'This grade level does not transition into Senior Secondary.'
        );

        $pendingExceptionEnrollmentIds = ProgressionException::query()->where('status', 'pending')->pluck('enrollment_id');
        $activeEnrollments = StudentEnrollment::query()
            ->where('grade_level_id', $gradeLevel->id)
            ->where('status', 'active')
            ->whereNotIn('id', $pendingExceptionEnrollmentIds)
            ->with('student')
            ->get();

        $academicYear = $activeEnrollments->countBy('academic_year')->sortDesc()->keys()->first() ?? (string) date('Y');
        $window = $this->eligibility->windowStatus($academicYear);

        $eligible = collect();
        $ineligible = collect();

        foreach ($activeEnrollments as $enrollment) {
            if ($this->eligibility->hasCompletedAllTerms($enrollment->student, $enrollment->academic_year)) {
                $eligible->push($enrollment);
            } else {
                $enrollment->missing_terms = $this->eligibility->missingTerms($enrollment->student, $enrollment->academic_year);
                $ineligible->push($enrollment);
            }
        }

        $pathways = Pathway::query()->where('status', 'active')->orderBy('name')->get();

        return view('curriculum.progression.classify-pathways', [
            'gradeLevel'     => $gradeLevel,
            'nextGradeLevel' => $nextGradeLevel,
            'window'         => $window,
            'eligible'       => $eligible->sortBy(fn ($e) => $e->student->full_name)->values(),
            'ineligible'     => $ineligible->sortBy(fn ($e) => $e->student->full_name)->values(),
            'pathways'       => $pathways,
        ]);
    }

    public function store(StorePathwayClassificationRequest $request, GradeLevel $gradeLevel)
    {
        $nextGradeLevel = $gradeLevel->nextGradeLevel();
        abort_unless($nextGradeLevel && $nextGradeLevel->isSeniorSecondaryEntryGrade(), 422);

        $validated = $request->validated();

        $academicYear = collect($validated['classifications'])
            ->map(fn ($c) => StudentEnrollment::find($c['enrollment_id'])?->academic_year)
            ->filter()->countBy()->sortDesc()->keys()->first() ?? (string) date('Y');

        $window = $this->eligibility->windowStatus($academicYear);
        if (! $window['open']) {
            return back()->withInput()->with('error', 'Progression window is closed: ' . $window['reason']);
        }

        $count = 0;

        DB::transaction(function () use ($validated, $nextGradeLevel, &$count) {
            foreach ($validated['classifications'] as $entry) {
                $enrollment = StudentEnrollment::query()->find($entry['enrollment_id']);

                if (! $enrollment || $enrollment->status !== 'active') {
                    continue;
                }

                if (! $this->eligibility->hasCompletedAllTerms($enrollment->student, $enrollment->academic_year)) {
                    continue; // safety net — should already be excluded client-side
                }

                $enrollment->update(['status' => 'promoted', 'exited_on' => now()]);

                StudentEnrollment::query()->create([
                    'user_id'        => $enrollment->user_id,
                    'grade_level_id' => $nextGradeLevel->id,
                    'stream_id'      => null,
                    'pathway_id'     => $entry['pathway_id'],
                    'academic_year'  => $validated['new_academic_year'],
                    'status'         => 'active',
                    'enrolled_on'    => now(),
                ]);

                $count++;
            }
        });

        return redirect()->route('curriculum.progression.index')
            ->with('success', "{$count} student(s) classified into pathways and promoted to {$nextGradeLevel->name}.");
    }
}
