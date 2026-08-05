<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\GradingBand;
use App\Models\LearningArea;
use App\Models\Stream;
use App\Models\TermSubjectResult;
use Illuminate\Http\Request;

class TermSubjectResultController extends Controller
{
    /** Preview + finalize screen: averages every assessment for this subject/class/term per student. */
    public function preview(Request $request, Stream $stream, LearningArea $learningArea, AcademicTerm $academicTerm)
    {
        $this->authorizeSubjectAccess($request, $learningArea, $stream);

        $enrollments = \App\Models\StudentEnrollment::query()
            ->where('stream_id', $stream->id)->where('status', 'active')
            ->with(['student', 'assessmentResults' => fn ($q) => $q
                ->whereHas('assessment', fn ($aq) => $aq
                    ->where('learning_area_id', $learningArea->id)
                    ->where('stream_id', $stream->id)
                    ->where('academic_term_id', $academicTerm->id)
                    ->whereIn('status', ['open', 'locked']))])
            ->get()
            ->sortBy(fn ($e) => $e->student->full_name)
            ->values();

        $isCompetency = \App\Models\Assessment::where('learning_area_id', $learningArea->id)
            ->where('stream_id', $stream->id)->where('academic_term_id', $academicTerm->id)
            ->whereHas('assessmentType', fn ($q) => $q->where('scoring_mode', 'competency'))->exists();

        $rows = $enrollments->map(function ($enrollment) use ($isCompetency) {
            $results = $enrollment->assessmentResults->where('is_absent', false);

            if ($isCompetency) {
                // Most frequent rating across all assessments this term -
                // a simple, defensible aggregation for competency-only subjects.
                $mode = $results->pluck('competency_level')->filter()->countBy()->sortDesc()->keys()->first();
                return ['enrollment' => $enrollment, 'average' => null, 'competency' => $mode, 'count' => $results->count()];
            }

            $avg = $results->pluck('score')->filter(fn ($s) => $s !== null)->avg();
            return ['enrollment' => $enrollment, 'average' => $avg, 'competency' => null, 'count' => $results->count()];
        });


        $existing = TermSubjectResult::query()
            ->where('academic_term_id', $academicTerm->id)->where('learning_area_id', $learningArea->id)
            ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
            ->get()->keyBy('student_enrollment_id');

//        return redirect()->route('results.assessments.index')->with('success', 'Term subject results updated.');

        return view('results.term-subject.preview', compact('stream', 'learningArea', 'academicTerm', 'rows', 'isCompetency', 'existing'));
    }

    public function finalize(Request $request, Stream $stream, LearningArea $learningArea, AcademicTerm $academicTerm)
    {
        $this->authorizeSubjectAccess($request, $learningArea, $stream);

        $validated = $request->validate([
            'remarks'                 => ['nullable', 'array'],
            'remarks.*'               => ['nullable', 'string', 'max:500'],
        ]);

        $enrollments = \App\Models\StudentEnrollment::query()
            ->where('stream_id', $stream->id)->where('status', 'active')
            ->with(['assessmentResults' => fn ($q) => $q
                ->whereHas('assessment', fn ($aq) => $aq
                    ->where('learning_area_id', $learningArea->id)
                    ->where('stream_id', $stream->id)
                    ->where('academic_term_id', $academicTerm->id))])
            ->get();

        $isCompetency = \App\Models\Assessment::where('learning_area_id', $learningArea->id)
            ->where('stream_id', $stream->id)->where('academic_term_id', $academicTerm->id)
            ->whereHas('assessmentType', fn ($q) => $q->where('scoring_mode', 'competency'))->exists();

        foreach ($enrollments as $enrollment) {
            $results = $enrollment->assessmentResults->where('is_absent', false);
            $payload = [
                'teacher_remarks' => $validated['remarks'][$enrollment->id] ?? null,
                'finalized_by'    => $request->user()->id,
                'finalized_at'    => now(),
            ];

            if ($isCompetency) {
                $payload['competency_level'] = $results->pluck('competency_level')->filter()->countBy()->sortDesc()->keys()->first();
                $payload['average_score'] = null;
                $payload['letter_grade'] = null;
            } else {
                $avg = $results->pluck('score')->filter(fn ($s) => $s !== null)->avg();
                $payload['average_score'] = $avg;
                $payload['letter_grade'] = $avg !== null ? GradingBand::letterFor($avg) : null;
                $payload['competency_level'] = null;
            }

            TermSubjectResult::query()->updateOrCreate(
                ['student_enrollment_id' => $enrollment->id, 'learning_area_id' => $learningArea->id, 'academic_term_id' => $academicTerm->id],
                $payload
            );
        }

        return redirect()->route('results.term-subject.preview', [$stream->id, $learningArea->id, $academicTerm->id])
            ->with('success', 'Subject results finalized for ' . $enrollments->count() . ' student(s).');
    }

    private function authorizeSubjectAccess(Request $request, LearningArea $learningArea, Stream $stream): void
    {
       $user = $request->user();
       if ($user->hasPermission('curriculum.manage') || $user->hasPermission('results.approve')) return;

       $allowed = \App\Models\SubjectTeacherAssignment::query()
            ->where('user_id', $user->id)->where('learning_area_id', $learningArea->id)
            ->where('stream_id', $stream->id)->where('status', 'active')->exists();

        abort_unless($allowed, 403);
    }
}
