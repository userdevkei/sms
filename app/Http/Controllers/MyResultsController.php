<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\GradingBand;
use App\Models\TermOverallResult;
use App\Models\TermSubjectResult;
use App\Services\Results\YearReportBuilder;
use Illuminate\Http\Request;

class MyResultsController extends Controller
{
    public function index(Request $request, YearReportBuilder $builder)
    {
        $user = $request->user();
        abort_unless($user->hasRole('student'), 403);

        $enrollmentIds = $user->enrollments()->pluck('id');
        $currentEnrollment = $user->currentEnrollment;

        // ---------- ASSESSMENT ----------
        $assessmentResults = Assessment::query()
            ->whereHas('results', fn ($q) => $q->whereIn('student_enrollment_id', $enrollmentIds))
            ->with([
                'results' => fn ($q) => $q->whereIn('student_enrollment_id', $enrollmentIds)
                    ->with('enrollment:id,stream_id'),
                'learningArea', 'academicTerm', 'assessmentType',
            ])
            ->latest('assessment_date')
            ->get();

        $assessmentGroups = $assessmentResults
            ->groupBy(fn ($a) => $a->name . '|' . ($a->academic_term_id ?? 'none'))
            ->map(function ($group) {
                $first = $group->first();
                $firstResult = $first->results->first();
                $enrollment = $firstResult?->enrollment;

                return [
                    'name' => $first->name,
                    'date' => $first->assessment_date,
                    'date_label' => $first->assessment_date?->format('d M Y') ?? '—',
                    'term_label' => $first->academicTerm
                        ? $first->academicTerm->academic_year . ' Term ' . $first->academicTerm->term_number
                        : null,
                    'pdf_url' => ($enrollment && $first->academic_term_id)
                        ? route('results.my-report-cards.assessment-pdf', [
                            $enrollment->stream_id, $first->academic_term_id, $first->name, $firstResult->student_enrollment_id,
                        ])
                        : null,
                    'subjects' => $group->map(function ($a) {
                        $score = $a->results->first()->score ?? null;

                        return [
                            'subject' => $a->learningArea->name ?? '—',
                            'type' => $a->assessmentType->name ?? '—',
                            'score' => $score,
                            'max_score' => $a->max_score,
                            'grade' => $score !== null ? (GradingBand::letterFor($score) ?? '—') : '—',
                        ];
                    })->values(),
                ];
            })
            ->sortByDesc('date')
            ->values();

        // ---------- Shared: all TermSubjectResult rows for this student ----------
        $allSubjectResults = TermSubjectResult::whereIn('student_enrollment_id', $enrollmentIds)
            ->with('learningArea')
            ->get();

        $subjectsByTermKey = $allSubjectResults->groupBy(
            fn ($r) => $r->student_enrollment_id . '|' . $r->academic_term_id
        );

        // ---------- TERM ----------
        $termResults = TermOverallResult::whereIn('student_enrollment_id', $enrollmentIds)
            ->where('status', 'published')
            ->with('academicTerm')
            ->get()
            ->sortByDesc(fn ($r) => $r->academicTerm->academic_year . str_pad($r->academicTerm->term_number, 2, '0', STR_PAD_LEFT))
            ->values()
            ->map(function ($r) use ($subjectsByTermKey) {
                $key = $r->student_enrollment_id . '|' . $r->academic_term_id;

                return [
                    'term_label' => $r->academicTerm->academic_year . ' Term ' . $r->academicTerm->term_number,
                    'average_score' => $r->average_score,
                    'grade' => $r->average_score !== null ? (GradingBand::letterFor($r->average_score) ?? '—') : '—',
                    'position_label' => ($r->position_in_stream ?? '-') . ' / ' . ($r->stream_size ?? '-'),
                    'pdf_url' => route('results.my-report-cards.pdf', $r->id),
                    'subjects' => ($subjectsByTermKey[$key] ?? collect())->map(fn ($s) => [
                        'subject' => $s->learningArea->name ?? '—',
                        'average_score' => $s->average_score,
                        // letter_grade already stored on TermSubjectResult — trust it over
                        // recomputing, since it may have been set via competency-based rules
                        // rather than a pure GradingBand lookup.
                        'letter_grade' => $s->letter_grade ?? ($s->average_score !== null ? GradingBand::letterFor($s->average_score) : null) ?? '—',
                        'competency_level' => $s->competency_level,
                    ])->values(),
                ];
            });

        // ---------- YEAR ----------
        $yearResults = collect();
        $builtCache = [];

        foreach ($user->enrollments()->with('stream')->orderByDesc('academic_year')->get() as $enrollment) {
            if ($enrollment->stream_id == null) {
                continue;
            }
            $cacheKey = $enrollment->stream_id . '-' . $enrollment->academic_year;

            if (! isset($builtCache[$cacheKey])) {
                $builtCache[$cacheKey] = collect($builder->build($enrollment->stream, $enrollment->academic_year))
                    ->keyBy(fn ($s) => $s['enrollment']->id);
            }

/*            if ($result = $builtCache[$cacheKey][$enrollment->id] ?? null) {
                // NOTE: 'subjects' comes straight from build()'s own
                // buildSubjectBreakdown() — do NOT recompute/overwrite it here.
                // A prior version of this code clobbered it with a naive
                // TermSubjectResult average, which is what broke the PDF.
                $result['grade'] = $result['yearly_average'] !== null
                    ? (GradingBand::letterFor($result['yearly_average']) ?? '—')
                    : '—';

                $result['pdf_url'] = route('results.my-report-cards.year-pdf', [
                    $enrollment->stream_id, $enrollment->academic_year, $enrollment->id,
                ]);

                $yearResults->push($result);
            }*/

            if ($result = $builtCache[$cacheKey][$enrollment->id] ?? null) {
                // NOTE: base 'subjects' shape still comes straight from build()'s own
                // buildSubjectBreakdown() — we only fill in gaps here, never replace it.
                $result['subjects'] = collect($result['subjects'])->map(function ($subject) {
                    $termAverages = [];

                    foreach ($subject['terms'] as $termKey => $term) {
                        if ($term['average_score'] !== null) {
                            $termAverages[] = (float) $term['average_score'];

                            // Some TermSubjectResult rows were saved without a
                            // letter_grade (competency-based rounds, manual entry
                            // gaps, etc). Backfill from GradingBand so every scored
                            // term shows a grade, matching term/assessment tabs.
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

                $result['pdf_url'] = route('results.my-report-cards.year-pdf', [
                    $enrollment->stream_id, $enrollment->academic_year, $enrollment->id,
                ]);

                $yearResults->push($result);
            }
        }

        $currentYearResult = $currentEnrollment
            ? $yearResults->first(fn ($r) => $r['enrollment']->id === $currentEnrollment->id)
            : null;

        return view('results.my-results.index', compact(
            'assessmentGroups', 'termResults', 'yearResults', 'currentYearResult', 'currentEnrollment'
        ));
    }
}
