<?php

namespace App\Services\Results;

use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\GradingBand;
use App\Models\Stream;
use App\Models\StudentEnrollment;

class AssessmentRoundReportBuilder
{
    public function build(Stream $stream, AcademicTerm $academicTerm, string $name): array
    {
        $assessments = Assessment::query()
            ->where('stream_id', $stream->id)
            ->where('academic_term_id', $academicTerm->id)
            ->where('name', $name)
            ->with('learningArea')
            ->get();

        $enrollments = StudentEnrollment::query()
            ->where('stream_id', $stream->id)
            ->with('student')->get()->keyBy('id');

        $totalEnrollments = $enrollments->count();

        // learning_area_id => ['subject' => name, 'max_score' => ..., 'scores' => [enrollment_id => score]]
        $subjectScores = [];

        foreach ($assessments as $assessment) {
            $results = AssessmentResult::query()
                ->where('assessment_id', $assessment->id)
                ->where('is_absent', false)
                ->whereNotNull('score')
                ->get();

            $subjectScores[$assessment->learning_area_id] = [
                'subject'   => $assessment->learningArea->name,
                'max_score' => $assessment->max_score,
                'scores'    => $results->pluck('score', 'student_enrollment_id'),
                'comments'  => $results->pluck('remarks', 'student_enrollment_id'),
            ];
        }

        $totalSubjects = count($subjectScores);

        // Subject-level rank: highest score = rank 1, within this stream, for this round only.
        // Every active enrollment is ranked, even students without a score for this subject
        // (e.g. absent) - they share the last position, so rank/out_of reflects the whole
        // active stream rather than only those who sat the assessment.
        $subjectRanks = [];
        foreach ($subjectScores as $subjectId => $data) {
            $scoredIds = $data['scores']->sortDesc()->keys()->values();

            foreach ($scoredIds as $index => $enrollmentId) {
                $subjectRanks[$subjectId][$enrollmentId] = $index + 1;
            }

            $lastRank = $scoredIds->count() + 1;
            foreach ($enrollments->keys() as $enrollmentId) {
                if (! isset($subjectRanks[$subjectId][$enrollmentId])) {
                    $subjectRanks[$subjectId][$enrollmentId] = $lastRank;
                }
            }
        }

        $students = [];
        $gradingBands = GradingBand::orderBy('min_score')->get();
        foreach ($enrollments as $enrollmentId => $enrollment) {
            $subjects = [];
            $total = 0;
            $count = 0;

            foreach ($subjectScores as $subjectId => $data) {
                $score = $data['scores'][$enrollmentId] ?? null;
                if ($score !== null) {
                    $total += $score;
                    $count++;
                }

                $gradingBand = $gradingBands->first(function ($band) use ($score) {
                    return $score >= $band->min_score && $score <= $band->max_score;
                });

                $subjects[] = [
                    'name'          => $data['subject'],
                    'score'         => round($score),
                    'max_score'     => $data['max_score'],
                    'class_average' => $data['scores']->isNotEmpty() ? $data['scores']->avg() : null,
                    'rank'          => $subjectRanks[$subjectId][$enrollmentId] ?? null,
                    'out_of'        => $totalEnrollments,
                    'comments'      => $data['comments'][$enrollmentId] ?? null,
                    'letter_grade'  => $gradingBand?->letter_grade,
                ];
            }

            // Total: sum of marks actually scored (absences contribute nothing, not a
            // separate penalty). Average: absences count as 0, divided by every subject
            // assessed this round - not just the ones the student sat.
            $students[$enrollmentId] = [
                'enrollment'  => $enrollment,
                'subjects'    => $subjects,
                'total'       => $count > 0 ? $total : null,
                'average'     => $totalSubjects > 0 ? round($total / $totalSubjects, 2) : null,
                'position'    => null,
                'stream_size' => null,
            ];
        }

        // Overall rank within the stream, based on average across all subjects assessed
        // this round (absences included as 0). A student with no assessments in this
        // round at all (no scores, no subjects) is excluded via the null check below.
        $ranked = collect($students)->filter(fn ($s) => $s['average'] !== null)->sortByDesc('average')->keys()->values();
        $streamSize = $ranked->count();

        foreach ($ranked as $index => $enrollmentId) {
            $students[$enrollmentId]['position'] = $index + 1;
            $students[$enrollmentId]['stream_size'] = $streamSize;
        }

        return collect($students)
            ->sortBy(fn ($s) => $s['position'] ?? PHP_INT_MAX)
            ->values()
            ->all();
    }
}
