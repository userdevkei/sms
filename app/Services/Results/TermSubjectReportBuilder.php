<?php

namespace App\Services\Results;

use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\GradingBand;
use App\Models\Stream;
use App\Models\StudentEnrollment;
use App\Models\TermSubjectResult;

class TermSubjectReportBuilder
{
    /**
     * Returns:
     * [
     *   'rounds'   => ['Opener', 'Mid Term', 'End Term'],  // distinct round names this term - used as table columns
     *   'students' => [ student_enrollment_id => [ subject rows ] ]
     * ]
     *
     * Each subject row: subject, rounds (per-round raw score), average_score,
     * letter_grade, competency_level, remarks, rank, out_of.
     * Rank is computed fresh each call - not persisted (same approach as the
     * single-round builder).
     */
    public function build(Stream $stream, AcademicTerm $academicTerm): array
    {
        $enrollmentIds = StudentEnrollment::query()
            ->where('stream_id', $stream->id)
            ->pluck('id');

        $results = TermSubjectResult::query()
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->where('academic_term_id', $academicTerm->id)
            ->with('learningArea')
            ->get();

        // Subject-level rank, based on the finalized average - not stored anywhere.
        $ranks = [];
        foreach ($results->groupBy('learning_area_id') as $subjectId => $group) {
            $ordered = $group->whereNotNull('average_score')->sortByDesc('average_score')->values();
            foreach ($ordered as $index => $result) {
                $ranks[$subjectId][$result->student_enrollment_id] = ['rank' => $index + 1, 'out_of' => $ordered->count()];
            }
        }

        // Raw per-round scores, so the report can show one column per exam (Opener/Mid Term/End Term).
        $assessments = Assessment::query()
            ->where('stream_id', $stream->id)
            ->where('academic_term_id', $academicTerm->id)
            ->with('learningArea')
            ->get();

        $roundNames = $assessments->pluck('name')->unique()->values()->all();

        // learning_area_id => [round_name => [enrollment_id => score]]
        $roundScores = [];
        foreach ($assessments as $assessment) {
            $scores = AssessmentResult::query()
                ->where('assessment_id', $assessment->id)
                ->where('is_absent', false)
                ->whereNotNull('score')
                ->pluck('score', 'student_enrollment_id');

            $roundScores[$assessment->learning_area_id][$assessment->name] = $scores;
        }

        // Class (stream) average per subject, for the performance chart.
        $classAverages = $results->groupBy('learning_area_id')
            ->map(fn ($group) => $group->whereNotNull('average_score')->avg('average_score'));

        // Subject teacher per learning_area, from the actual teaching assignment -
        // not finalized_by, which only records who clicked Finalize (often an admin).
        $teacherAssignments = \App\Models\SubjectTeacherAssignment::query()
            ->where('stream_id', $stream->id)
            ->where('academic_year', $academicTerm->academic_year)
            ->where('status', 'active')
            ->with('teacher')
            ->get()
            ->keyBy('learning_area_id');

        $gradingBands = GradingBand::orderBy('min_score')->get();

        $students = $results->groupBy('student_enrollment_id')->map(function ($group) use ($ranks, $roundScores, $roundNames, $classAverages, $teacherAssignments, $gradingBands) {
            return $group->map(function ($result) use ($ranks, $roundScores, $roundNames, $classAverages, $teacherAssignments, $gradingBands) {
                $rankInfo = $ranks[$result->learning_area_id][$result->student_enrollment_id] ?? null;

                $rounds = [];
                foreach ($roundNames as $roundName) {
                    $roundScore = $roundScores[$result->learning_area_id][$roundName][$result->student_enrollment_id] ?? null;
                    $rounds[$roundName] = $roundScore !== null ? round($roundScore) : null;
                }

                $teacher = ($teacherAssignments[$result->learning_area_id] ?? null)?->teacher;

                $teacherName = $teacher
                    ? (
                        (ucwords($teacher->gender) === 'Male' ? 'Mr.' : 'Ms.') . ' ' .
                        strtoupper(substr($teacher->first_name, 0, 1)) . '. ' .
                        $teacher->last_name
                    )
                    : null;

                $score = (int) round($result->average_score);

                $gradingBand = $gradingBands->first(function ($band) use ($score) {
                    return $score >= $band->min_score && $score <= $band->max_score;
                });

                return [
                    'subject'          => $result->learningArea->name,
                    'rounds'           => $rounds,
                    'average_score'    => $score,
                    'class_average'    => $classAverages[$result->learning_area_id] ?? null,
                    'letter_grade'     => $gradingBand?->letter_grade,
                    'competency_level' => $result->competency_level,
                    'remarks'          => $result->teacher_remarks,
                    'teacher'          => $teacherName,
                    'rank'             => $rankInfo['rank'] ?? null,
                    'out_of'           => $rankInfo['out_of'] ?? null,
                ];
            })->values()->all();
        })->all();

        return ['rounds' => $roundNames, 'students' => $students];
    }
}
