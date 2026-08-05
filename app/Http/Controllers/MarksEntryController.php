<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMarksRequest;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\StudentEnrollment;
use App\Models\SubjectTeacherAssignment;
use Illuminate\Support\Facades\DB;

class MarksEntryController extends Controller
{
/*    public function edit(Assessment $assessment)
    {
        $this->authorizeAssessmentAccess($assessment);

        $enrollments = StudentEnrollment::query()
            ->where('grade_level_id', $assessment->stream->grade_level_id)
            ->where('stream_id', $assessment->stream_id)
            ->where('status', 'active')
            ->with(['student', 'assessmentResults' => fn ($q) => $q->where('assessment_id', $assessment->id)])
            ->get()
            ->sortBy(fn ($e) => $e->student->full_name)
            ->values();

        return view('results.marks-entry.edit', compact('assessment', 'enrollments'));
    }*/

    public function edit(Assessment $assessment)
    {
        $this->authorizeAssessmentAccess($assessment);

        $enrollments = StudentEnrollment::query()
            ->where('grade_level_id', $assessment->stream->grade_level_id)
            ->where('stream_id', $assessment->stream_id)
            ->with(['student', 'assessmentResults' => fn ($q) => $q->where('assessment_id', $assessment->id)])
            ->get()
            ->sortBy(fn ($e) => $e->student->full_name)
            ->values();

        // Finalize is only meaningful once the subject has more than one assessment
        // with marks entered this term - averaging a single assessment isn't a
        // "term result" yet, it's just that one CAT/exam.
        $assessmentsWithMarksCount = Assessment::query()
            ->where('learning_area_id', $assessment->learning_area_id)
            ->where('stream_id', $assessment->stream_id)
            ->where('academic_term_id', $assessment->academic_term_id)
            ->where('assessments.id', $assessment->id)
            ->whereHas('results')
            ->count();

        $canFinalize = $assessmentsWithMarksCount >= 1;

        return view('results.marks-entry.edit', compact('assessment', 'enrollments', 'canFinalize'));
    }

    public function update(StoreMarksRequest $request, Assessment $assessment)
    {
        $this->authorizeAssessmentAccess($assessment);

        abort_if($assessment->status === 'locked', 422, 'This assessment is locked - marks can no longer be changed.');
        abort_if($assessment->status === 'void', 422, 'This assessment has been voided.');

        $validated = $request->validated();
        $isCompetency = $assessment->isCompetencyBased();

        DB::transaction(function () use ($validated, $assessment, $isCompetency, $request) {
            foreach ($validated['results'] as $entry) {
                $isAbsent = ! empty($entry['is_absent']);

                AssessmentResult::query()->updateOrCreate(
                    ['assessment_id' => $assessment->id, 'student_enrollment_id' => $entry['enrollment_id']],
                    [
                        'score'            => $isAbsent ? null : ($isCompetency ? null : ($entry['score'] ?? null)),
                        'competency_level' => $isAbsent ? null : ($isCompetency ? ($entry['competency_level'] ?? null) : null),
                        'is_absent'        => $isAbsent,
                        'remarks'          => $entry['remarks'] ?? null,
                        'entered_by'       => $request->user()->id,
                        'entered_at'       => now(),
                    ]
                );
            }

            if ($assessment->status === 'draft') {
                $assessment->update(['status' => 'open']);
            }
        });

        return back()->with('success', 'Marks saved successfully.');
    }

    private function authorizeAssessmentAccess(Assessment $assessment): void
    {
        $user = request()->user();

        if ($user->hasPermission('curriculum.manage') || $user->hasPermission('results.approve')) {
            return;
        }

        $allowed = SubjectTeacherAssignment::query()
            ->where('user_id', $user->id)
            ->where('learning_area_id', $assessment->learning_area_id)
            ->where('stream_id', $assessment->stream_id)
            ->where('status', 'active')
            ->exists();

        abort_unless($allowed, 403, 'You are not assigned to teach this subject in this class.');
    }
}
