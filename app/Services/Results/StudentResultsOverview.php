<?php

namespace App\Services\Results;

use App\Models\Assessment;
use App\Models\GradingBand;
use App\Models\TermOverallResult;
use App\Models\TermSubjectResult;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;

class StudentResultsOverview
{
    public function __construct(private YearReportBuilder $builder) {}

    /**
     * @param Closure $url fn (string $kind, array $params): ?string. Return null to hide the PDF button.
     */
    public function build(User $student, Closure $url): array
    {
        $currentEnrollment = $student->currentEnrollment;
        $yearResults       = $this->years($student, $url);

        return [
            'assessmentGroups'  => $this->assessments($student, $url),
            'termResults'       => $this->terms($student, $url),
            'yearResults'       => $yearResults,
            'currentYearResult' => $currentEnrollment
                ? $yearResults->first(fn ($r) => $r['enrollment']->id === $currentEnrollment->id)
                : null,
            'currentEnrollment' => $currentEnrollment,
        ];
    }

    public function assessments(User $student, Closure $url): Collection
    {
        $enrollmentIds = $student->enrollments()->pluck('id');

        return Assessment::query()
            ->whereHas('results', fn ($q) => $q->whereIn('student_enrollment_id', $enrollmentIds))
            ->with([
                'results' => fn ($q) => $q->whereIn('student_enrollment_id', $enrollmentIds)
                    ->with('enrollment:id,stream_id'),
                'learningArea', 'academicTerm', 'assessmentType',
            ])
            ->latest('assessment_date')
            ->get()
            ->groupBy(fn ($a) => $a->name . '|' . ($a->academic_term_id ?? 'none'))
            ->map(function ($group) use ($url) {
                $first       = $group->first();
                $firstResult = $first->results->first();
                $enrollment  = $firstResult?->enrollment;

                return [
                    'name'          => $first->name,
                    'date'          => $first->assessment_date,
                    'date_label'    => $first->assessment_date?->format('d M Y') ?? '—',
                    'term_label'    => $first->academicTerm
                        ? $first->academicTerm->academic_year . ' Term ' . $first->academicTerm->term_number
                        : null,
                    'enrollment_id' => $firstResult?->student_enrollment_id,
                    'pdf_url'       => ($enrollment && $first->academic_term_id)
                        ? $url('assessment', [$enrollment->stream_id, $first->academic_term_id, $first->name, $firstResult->student_enrollment_id])
                        : null,
                    'subjects'      => $group->map(function ($a) {
                        $score = $a->results->first()->score ?? null;

                        return [
                            'subject'   => $a->learningArea->name ?? '—',
                            'type'      => $a->assessmentType->name ?? '—',
                            'score'     => $score,
                            'max_score' => $a->max_score,
                            'grade'     => $score !== null ? (GradingBand::letterFor($score) ?? '—') : '—',
                        ];
                    })->values(),
                ];
            })
            ->sortByDesc('date')
            ->values();
    }

    public function terms(User $student, Closure $url): Collection
    {
        $enrollmentIds = $student->enrollments()->pluck('id');

        $subjectsByTermKey = TermSubjectResult::whereIn('student_enrollment_id', $enrollmentIds)
            ->with('learningArea')
            ->get()
            ->groupBy(fn ($r) => $r->student_enrollment_id . '|' . $r->academic_term_id);

        return TermOverallResult::whereIn('student_enrollment_id', $enrollmentIds)
            ->where('status', 'published')
            ->with('academicTerm')
            ->get()
            ->sortByDesc(fn ($r) => $r->academicTerm->academic_year . str_pad($r->academicTerm->term_number, 2, '0', STR_PAD_LEFT))
            ->values()
            ->map(function ($r) use ($subjectsByTermKey, $url) {
                $key = $r->student_enrollment_id . '|' . $r->academic_term_id;

                return [
                    'term_label'     => $r->academicTerm->academic_year . ' Term ' . $r->academicTerm->term_number,
                    'average_score'  => $r->average_score,
                    'grade'          => $r->average_score !== null ? (GradingBand::letterFor($r->average_score) ?? '—') : '—',
                    'position_label' => ($r->position_in_stream ?? '-') . ' / ' . ($r->stream_size ?? '-'),
                    'enrollment_id'  => $r->student_enrollment_id,
                    'pdf_url'        => $url('term', [$r->id]),
                    'subjects'       => ($subjectsByTermKey[$key] ?? collect())->map(fn ($s) => [
                        'subject'          => $s->learningArea->name ?? '—',
                        'average_score'    => $s->average_score,
                        // Stored letter_grade wins: it may come from competency-based rules, not a pure band lookup.
                        'letter_grade'     => $s->letter_grade ?? ($s->average_score !== null ? GradingBand::letterFor($s->average_score) : null) ?? '—',
                        'competency_level' => $s->competency_level,
                    ])->values(),
                ];
            });
    }

    public function years(User $student, Closure $url): Collection
    {
        $yearResults = collect();
        $builtCache  = [];

        foreach ($student->enrollments()->with('stream')->orderByDesc('academic_year')->get() as $enrollment) {
            if ($enrollment->stream_id == null) {
                continue;
            }

            $cacheKey = $enrollment->stream_id . '-' . $enrollment->academic_year;

            if (! isset($builtCache[$cacheKey])) {
                $builtCache[$cacheKey] = collect($this->builder->build($enrollment->stream, $enrollment->academic_year))
                    ->keyBy(fn ($s) => $s['enrollment']->id);
            }

            if ($result = $builtCache[$cacheKey][$enrollment->id] ?? null) {
                // Base 'subjects' shape comes from build(): only fill gaps here, never replace it.
                $result['subjects'] = collect($result['subjects'])->map(function ($subject) {
                    $termAverages = [];

                    foreach ($subject['terms'] as $termKey => $term) {
                        if ($term['average_score'] !== null) {
                            $termAverages[] = (float) $term['average_score'];

                            // Some rows were saved without a letter_grade: backfill from GradingBand.
                            if (empty($term['letter_grade']) && empty($term['competency_level'])) {
                                $subject['terms'][$termKey]['letter_grade'] = GradingBand::letterFor((float) $term['average_score']) ?? '—';
                            }
                        }
                    }

                    $subject['year_average'] = count($termAverages) > 0
                        ? round(array_sum($termAverages) / count($termAverages), 2)
                        : null;

                    $subject['year_grade'] = $subject['year_average'] !== null
                        ? (GradingBand::letterFor($subject['year_average']) ?? '—')
                        : '—';

                    return $subject;
                })->all();

                $result['grade'] = $result['yearly_average'] !== null
                    ? (GradingBand::letterFor($result['yearly_average']) ?? '—')
                    : '—';

                $result['pdf_url'] = $url('year', [$enrollment->stream_id, $enrollment->academic_year, $enrollment->id]);

                $yearResults->push($result);
            }
        }

        return $yearResults;
    }
}
