<?php

namespace App\Services\Results;

use App\Models\AcademicTerm;
use App\Models\Stream;
use App\Models\TermOverallResult;
use App\Models\TermSubjectResult;

class ReportCardCompiler
{
    public function compileForStream(Stream $stream, AcademicTerm $academicTerm): int
    {
        $enrollments = \App\Models\StudentEnrollment::query()
            ->where('stream_id', $stream->id)->where('status', 'active')->get();

        $rows = $enrollments->map(function ($enrollment) use ($academicTerm) {
            $subjectResults = TermSubjectResult::query()
                ->where('student_enrollment_id', $enrollment->id)
                ->where('academic_term_id', $academicTerm->id)
                ->whereNotNull('average_score')
                ->get();

            return [
                'enrollment_id' => $enrollment->id,
                'total' => $subjectResults->sum('average_score'),
                'average' => $subjectResults->isNotEmpty() ? $subjectResults->avg('average_score') : null,
                'subject_count' => $subjectResults->count(),
            ];
        })->filter(fn ($r) => $r['average'] !== null);

//        return $rows;

        // Rank within stream, based on average score, highest first.
        $rankedInStream = $rows->sortByDesc('average')->values();
        $streamSize = $rankedInStream->count();

        // Rank within the whole grade level (across all streams of that grade).
        $gradeLevelId = $stream->grade_level_id;
        $gradeEnrollmentIds = \App\Models\StudentEnrollment::query()
            ->where('grade_level_id', $gradeLevelId)->where('status', 'active')->pluck('id');

        $gradeRows = TermSubjectResult::query()
            ->whereIn('student_enrollment_id', $gradeEnrollmentIds)
            ->where('academic_term_id', $academicTerm->id)
            ->whereNotNull('average_score')
            ->get()
            ->groupBy('student_enrollment_id')
            ->map(fn ($group) => $group->avg('average_score'))
            ->sortDesc();

        $gradeSize = $gradeRows->count();
        $gradeRanks = array_flip($gradeRows->keys()->all()); // enrollment_id => 0-based rank

        $count = 0;

        foreach ($rankedInStream as $index => $row) {
            $positionInStream = $index + 1;
            $positionInGrade = isset($gradeRanks[$row['enrollment_id']]) ? $gradeRanks[$row['enrollment_id']] + 1 : null;

            TermOverallResult::query()->updateOrCreate(
                ['student_enrollment_id' => $row['enrollment_id'], 'academic_term_id' => $academicTerm->id],
                [
                    'total_score'        => $row['total'],
                    'average_score'      => $row['average'],
                    'position_in_stream' => $positionInStream,
                    'stream_size'        => $streamSize,
                    'position_in_grade'  => $positionInGrade,
                    'grade_size'         => $gradeSize,
                    'status'             => 'draft', // compiling never auto-publishes
                ]
            );
            $count++;
        }

        return $count;
    }
}
