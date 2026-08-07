<?php

namespace App\Services\Results;

use App\Models\Stream;
use App\Models\StudentEnrollment;
use App\Models\TermOverallResult;
use App\Models\TermSubjectResult;

class YearReportBuilder
{
    /**
     * Combines up to 3 terms (matched by academic_year) of already-PUBLISHED
     * TermOverallResult rows per student, side by side, plus a yearly average
     * (mean of whichever term averages are available) and a yearly rank
     * computed within the stream. Also builds a per-subject breakdown
     * (see buildSubjectBreakdown()) so report cards can show each subject's
     * score across all three terms, its live rank within that subject/term,
     * and its most recent teacher remark.
     *
     * Assumption: only published terms count toward the yearly average -
     * drafts are excluded since they haven't been signed off yet.
     */
    public function build(Stream $stream, string $academicYear, array $termNumbers = [1, 2, 3]): array
    {
        $enrollments = StudentEnrollment::query()
            ->where('stream_id', $stream->id)
            ->with('student')->get()->keyBy('id');

        $results = TermOverallResult::query()
            ->whereHas('enrollment', fn ($q) => $q->where('stream_id', $stream->id))
            ->whereHas('academicTerm', fn ($q) => $q->where('academic_year', $academicYear)->whereIn('term_number', $termNumbers))
            ->where('status', 'published')
            ->with('academicTerm')
            ->get()
            ->groupBy('student_enrollment_id');

        $subjectsByEnrollment = $this->buildSubjectBreakdown($stream, $academicYear, $termNumbers);

        $students = [];
        foreach ($enrollments as $enrollmentId => $enrollment) {
            $terms = [];
            $averages = [];

            foreach ($results->get($enrollmentId, collect()) as $result) {
                $terms['T' . $result->academicTerm->term_number] = [
                    'average'     => $result->average_score,
                    'position'    => $result->position_in_stream,
                    'stream_size' => $result->stream_size,
                ];
                if ($result->average_score !== null) {
                    $averages[] = $result->average_score;
                }
            }

            $students[$enrollmentId] = [
                'enrollment'     => $enrollment,
                'terms'          => $terms,
                'subjects'       => $subjectsByEnrollment[$enrollmentId] ?? [],
                'yearly_average' => count($averages) > 0 ? round(array_sum($averages) / count($averages), 2) : null,
                'yearly_position'=> null,
                'yearly_size'    => null,
            ];
        }

        $ranked = collect($students)->filter(fn ($s) => $s['yearly_average'] !== null)->sortByDesc('yearly_average')->keys()->values();
        $size = $ranked->count();

        foreach ($ranked as $index => $enrollmentId) {
            $students[$enrollmentId]['yearly_position'] = $index + 1;
            $students[$enrollmentId]['yearly_size'] = $size;
        }

        return collect($students)->values()->all();
    }

    /**
     * Builds, per student enrollment, a list of subjects with:
     *   - average_score for each of T1/T2/T3 (whichever are finalized)
     *   - a live rank computed by comparing average_score against every
     *     other student in the stream, for THAT specific subject+term
     *   - the "display" position/remarks pulled from the most recent term
     *     that has a finalized result (T3 if present, else T2, else T1) -
     *     since a single subject row only has room for one position and
     *     one remarks value, not three.
     *
     * Rank is computed only among students who have a numeric average_score
     * for that subject/term - competency-only subjects (no average_score,
     * e.g. PP1-style EE/ME/AE/BE ratings) are excluded from ranking since
     * there's nothing numeric to compare.
     */
    private function buildSubjectBreakdown(Stream $stream, string $academicYear, array $termNumbers): array
    {
        $termNumberToLabel = fn (int $n) => 'T' . $n;

        $results = TermSubjectResult::query()
            ->whereHas('enrollment', fn ($q) => $q->where('stream_id', $stream->id)/*->where('status', 'active')*/)
            ->whereHas('academicTerm', fn ($q) => $q->where('academic_year', $academicYear)->whereIn('term_number', $termNumbers))
            ->whereNotNull('finalized_at')
            ->with(['learningArea', 'academicTerm'])
            ->get();

        // Group by learning_area_id, then term number, then collect all
        // enrollments' scores together so we can rank within each group.
        $bySubjectAndTerm = $results->groupBy(fn ($r) => $r->learning_area_id . '|' . $r->academicTerm->term_number);

        // subjectId => term_number => enrollmentId => rank data
        $ranksLookup = [];

        foreach ($bySubjectAndTerm as $key => $group) {
            [$subjectId, $termNumber] = explode('|', $key);

            $ranked = $group
                ->filter(fn ($r) => $r->average_score !== null)
                ->sortByDesc('average_score')
                ->values();

            $rankSize = $ranked->count();

            foreach ($ranked as $index => $result) {
                $ranksLookup[$subjectId][$termNumber][$result->student_enrollment_id] = [
                    'position' => $index + 1,
                    'size'     => $rankSize,
                ];
            }
        }

        // enrollmentId => subjectId => subject row data
        $subjectsByEnrollment = [];

        foreach ($results->groupBy('student_enrollment_id') as $enrollmentId => $studentResults) {
            $subjectRows = [];

            foreach ($studentResults->groupBy('learning_area_id') as $subjectId => $subjectResults) {
                $termData = [];
                $mostRecentResult = null;
                $mostRecentTermNumber = null;

                foreach ($subjectResults as $result) {
                    $termNumber = $result->academicTerm->term_number;
                    $termData[$termNumberToLabel($termNumber)] = [
                        'average_score'    => $result->average_score,
                        'letter_grade'     => $result->letter_grade,
                        'competency_level' => $result->competency_level,
                    ];

                    // Track the highest term_number seen so far as "most recent" -
                    // used to pick which term's position/remarks to surface.
                    if ($mostRecentTermNumber === null || $termNumber > $mostRecentTermNumber) {
                        $mostRecentTermNumber = $termNumber;
                        $mostRecentResult = $result;
                    }
                }

                $rank = $mostRecentResult
                    ? ($ranksLookup[$subjectId][$mostRecentTermNumber][$enrollmentId] ?? null)
                    : null;

                $subjectRows[$subjectId] = [
                    'name'    => $subjectResults->first()->learningArea->name ?? '—',
                    'terms'   => $termData,
                    'position'=> $rank ? "{$rank['position']} / {$rank['size']}" : '-',
                    'remarks' => $mostRecentResult->teacher_remarks ?? '-',
                ];
            }

            // Order subjects alphabetically by name for consistent report layout.
            $subjectsByEnrollment[$enrollmentId] = collect($subjectRows)->sortBy('name')->values()->all();
        }

        return $subjectsByEnrollment;
    }
}
