<?php

namespace App\Services\Results;

use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Stream;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;

class AssessmentReportCompiler
{
    /**
     * Raw, on-the-fly compile for ONE assessment round (e.g. "Opener") across
     * every subject in a stream/term. Not persisted anywhere - TermSubjectResult
     * already averages ACROSS rounds, this deliberately looks at just one round,
     * so it's recomputed per request rather than stored.
     */
    public function compileForRound(Stream $stream, AcademicTerm $academicTerm, string $name): Collection
    {
        $assessments = Assessment::query()
            ->where('stream_id', $stream->id)
            ->where('academic_term_id', $academicTerm->id)
            ->where('name', $name)
            ->whereIn('status', ['open', 'locked'])
            ->with('learningArea')
            ->get();

        $enrollments = StudentEnrollment::query()
            ->where('stream_id', $stream->id)->where('status', 'active')
            ->with('student')->get();

        // learning_area_id => ['name' => ..., 'scores' => [enrollment_id => score]]
        $scoresBySubject = [];
        foreach ($assessments as $assessment) {
            $results = AssessmentResult::query()
                ->where('assessment_id', $assessment->id)
                ->where('is_absent', false)
                ->whereNotNull('score')
                ->get(['student_enrollment_id', 'score']);

            $scoresBySubject[$assessment->learning_area_id] = [
                'name' => $assessment->learningArea->name,
                'scores' => $results->pluck('score', 'student_enrollment_id'),
            ];
        }

        // Rank each subject independently (highest score first).
        $subjectRanks = [];
        foreach ($scoresBySubject as $learningAreaId => $data) {
            $ranked = $data['scores']->sortDesc()->keys()->values();
            foreach ($ranked as $index => $enrollmentId) {
                $subjectRanks[$learningAreaId][$enrollmentId] = $index + 1;
            }
        }

        $studentTotals = [];
        foreach ($enrollments as $enrollment) {
            $total = 0;
            $count = 0;
            foreach ($scoresBySubject as $data) {
                if (isset($data['scores'][$enrollment->id])) {
                    $total += $data['scores'][$enrollment->id];
                    $count++;
                }
            }
            $studentTotals[$enrollment->id] = $count > 0 ? ['total' => $total, 'average' => $total / $count] : null;
        }

        $rankedEnrollments = collect($studentTotals)->filter()->sortByDesc('average')->keys()->values();
        $streamSize = $rankedEnrollments->count();

        return $enrollments->map(function ($enrollment) use ($scoresBySubject, $subjectRanks, $studentTotals, $rankedEnrollments, $streamSize) {
            $subjects = [];
            foreach ($scoresBySubject as $learningAreaId => $data) {
                $score = $data['scores'][$enrollment->id] ?? null;
                $subjects[] = [
                    'name'   => $data['name'],
                    'score'  => $score,
                    'rank'   => $score !== null ? ($subjectRanks[$learningAreaId][$enrollment->id] ?? null) : null,
                    'out_of' => $data['scores']->count(),
                ];
            }

            $summary = $studentTotals[$enrollment->id] ?? null;
            $position = $rankedEnrollments->search($enrollment->id);

            return [
                'enrollment'         => $enrollment,
                'subjects'           => $subjects,
                'total'              => $summary['total'] ?? null,
                'average'            => $summary['average'] ?? null,
                'position_in_stream' => $position !== false ? $position + 1 : null,
                'stream_size'        => $streamSize,
            ];
        })->sortBy('position_in_stream')->values();
    }
}
