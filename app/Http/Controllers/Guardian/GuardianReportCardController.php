<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\GradingBand;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Results\StudentResultsOverview;
use App\Services\Results\YearReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mpdf\Mpdf;

class GuardianReportCardController extends Controller
{
    public function __construct(private StudentResultsOverview $overview) {}

    /* ───────────── Year (your template + YearReportBuilder) ───────────── */
    public function year(Request $request, User $child, string $enrollmentId, YearReportBuilder $builder)
    {
        // Scoped through the child's own enrollments: any other id is a 404.
        $enrollment = $child->enrollments()->with(['stream', 'student'])->findOrFail($enrollmentId);
        abort_unless($enrollment->stream, 404);

        $row = collect($builder->build($enrollment->stream, $enrollment->academic_year))
            ->first(fn ($s) => $s['enrollment']->id === $enrollment->id);
        abort_unless($row, 404);

        $html = view('guardian.pdf.year-pdf', [
            'row'          => $row,
            'stream'       => $enrollment->stream,
            'academicYear' => $enrollment->academic_year,
            'schoolName'   => setting('school_name', config('app.name')),
            'schoolPhone'  => setting('school_phone'),
            'logoPath'     => $this->resolveImageBase64(setting('logo_path')),
        ])->render();

        return $this->output($html, Str::slug($child->userID . ' year ' . $enrollment->academic_year), false, 'Year Report Card — ' . $child->full_name);
    }

    /* ───────────── Term (guardian.pdf.term-pdf, same template as the student PDF) ───────────── */
    public function term(Request $request, User $child, string $reportId)
    {
        // Only the child's own PUBLISHED term results are in this list, so any other id is a 404.
        $row = $this->overview->terms($child, fn ($kind, $p) => (string) $p[0])
            ->first(fn ($r) => $r['pdf_url'] === $reportId);
        abort_unless($row, 404);

        // Ownership: the enrollment must belong to this child.
        $enrollment = $child->enrollments()->with(['stream', 'student'])->findOrFail($row['enrollment_id']);
        abort_unless($enrollment->stream, 404);

        $term = $this->resolveTerm($row, $reportId);
        abort_unless($term, 404);

        $teacherAssignments = \App\Models\SubjectTeacherAssignment::query()
            ->where('stream_id', $enrollment->stream->id)
            ->where('academic_year', $term->academic_year)
            ->where('status', 'active')
            ->with('teacher')
            ->get()
            ->keyBy('learning_area_id');

        $streamIds = StudentEnrollment::where('stream_id', $enrollment->stream_id)->pluck('id');
        $gradeIds  = $enrollment->grade_level_id
            ? StudentEnrollment::where('grade_level_id', $enrollment->grade_level_id)
                ->where('academic_year', $enrollment->academic_year)
                ->pluck('id')
            : $streamIds;

        // Everyone in the grade (the stream is a subset of it). Only aggregates leave this method.
        [$scores, $rounds] = $this->termScores($gradeIds->all(), $term->id);

        $overviewSubjects = collect($row['subjects'] ?? []);
        $mine             = $scores[$enrollment->id] ?? [];

        $subjects = [];
        $myTotal  = 0.0;

        foreach (collect($mine)->sortBy('name') as $areaId => $data) {
            $computedAvg = $this->avg($data['rounds']);
            $ov          = $overviewSubjects->firstWhere('subject', $data['name']);

            // Prefer the figure the guardian already sees on the results page.
            $avg = isset($ov['average_score']) && $ov['average_score'] !== null
                ? (float) $ov['average_score']
                : $computedAvg;

            $streamAvgs = $this->areaAverages($scores, $streamIds->all(), $areaId);

            $subjects[] = [
                'subject'          => $data['name'],
                'rounds'           => $data['rounds'],
                'average_score'    => $avg,
                'competency_level' => $ov['competency_level'] ?? null,
                'letter_grade'     => $ov['letter_grade'] ?? ($avg !== null ? GradingBand::letterFor($avg) : null),
                'rank'             => $avg !== null ? 1 + $streamAvgs->filter(fn ($v) => $v > $avg)->count() : null,
                'out_of'           => $streamAvgs->count(),
                'class_average'    => $streamAvgs->isNotEmpty() ? round($streamAvgs->avg(), 1) : null,
                'teacher'          => $this->teacherName($teacherAssignments->get($areaId)?->teacher),
                'remarks'          => $ov['remarks'] ?? $ov['comment'] ?? null,
            ];

            if ($avg !== null) {
                $myTotal += $avg;
            }
        }

        $streamTotals = $this->totals($scores, $streamIds->all());
        $gradeTotals  = $this->totals($scores, $gradeIds->all());
        $hasTotal     = $myTotal > 0;

        $overall = (object) [
            'academicTerm'          => $term,
            'total_score'           => $hasTotal ? round($myTotal, 1) : null,
            'average_score'         => $row['average_score'] ?? null,
            'position_in_stream'    => $hasTotal ? 1 + $streamTotals->filter(fn ($t) => $t > $myTotal)->count() : null,
            'stream_size'           => $streamTotals->count(),
            'position_in_grade'     => $hasTotal ? 1 + $gradeTotals->filter(fn ($t) => $t > $myTotal)->count() : null,
            'grade_size'            => $gradeTotals->count(),
            'class_teacher_remarks' => $row['class_teacher_remarks'] ?? null,
        ];

        $html = view('guardian.pdf.term-pdf', [
            'enrollment'  => $enrollment,
            'overall'     => $overall,
            'rounds'      => $rounds,
            'subjects'    => $subjects,
            'schoolName'  => setting('school_name', config('app.name')),
            'schoolPhone' => setting('school_phone'),
            'logoPath'    => $this->resolveImageBase64(setting('logo_path')),
        ])->render();

        return $this->output(
            $html,
            Str::slug($child->userID . ' ' . ($row['term_label'] ?? 'term')),
            false,
            'Term Report Card — ' . $child->full_name
        );
    }

    /* ───────────── Assessment (your template) ───────────── */
    public function assessment(Request $request, User $child, string $enrollmentId, string $termId)
    {
        $name = (string) $request->query('name', '');
        abort_if($name === '', 404);

        // Ownership: the enrollment must belong to this child, otherwise 404.
        $enrollment = $child->enrollments()->with(['stream', 'student'])->findOrFail($enrollmentId);
        abort_unless($enrollment->stream, 404);

        $streamEnrollmentIds = StudentEnrollment::where('stream_id', $enrollment->stream_id)->pluck('id');

        $assessments = Assessment::query()
            ->where('name', $name)
            ->where('academic_term_id', $termId)
            ->whereHas('results', fn ($q) => $q->whereIn('student_enrollment_id', $streamEnrollmentIds))
            ->with([
                'learningArea', 'academicTerm',
                'results' => fn ($q) => $q->whereIn('student_enrollment_id', $streamEnrollmentIds),
            ])
            ->get();

        // The child must actually have a result in this assessment.
        abort_unless(
            $assessments->contains(fn ($a) => $a->results->contains('student_enrollment_id', $enrollment->id)),
            404
        );

        $row = $this->assessmentRow($assessments, $enrollment);

        $html = view('guardian.pdf.assessment-pdf', [
            'name'         => $name,
            'row'          => $row,
            'stream'       => $enrollment->stream,
            'academicTerm' => $assessments->first()->academicTerm,
            'schoolName'   => setting('school_name', config('app.name')),
            'schoolPhone'  => setting('school_phone'),
            'logoPath'     => $this->resolveImageBase64(setting('logo_path')),
        ])->render();

        return $this->output($html, Str::slug($child->userID . ' ' . $name), false, $name . ' Report Card — ' . $child->full_name);
    }

    /**
     * Builds the $row array the assessment template expects.
     * Only aggregate class figures (rank, average, size) leave the stream; no other student's marks are exposed.
     */
    private function assessmentRow($assessments, $enrollment): array
    {
        $subjects = [];
        $totals   = []; // enrollment_id => sum of scores across subjects

        foreach ($assessments as $a) {
            $scores = $a->results->mapWithKeys(fn ($r) => [
                $r->student_enrollment_id => $r->score !== null ? (float) $r->score : null,
            ]);

            foreach ($scores as $eid => $score) {
                if ($score !== null) {
                    $totals[$eid] = ($totals[$eid] ?? 0) + $score;
                }
            }

            $valid   = $scores->filter(fn ($s) => $s !== null);
            $mine    = $scores[$enrollment->id] ?? null;
            $mineRow = $a->results->firstWhere('student_enrollment_id', $enrollment->id);

            $subjects[] = [
                'name'          => $a->learningArea->name ?? '—',
                'score'         => $mine,
                'max_score'     => $a->max_score,
                'letter_grade'  => $mine !== null ? (GradingBand::letterFor($mine) ?? null) : null,
                'rank'          => $mine !== null ? 1 + $valid->filter(fn ($s) => $s > $mine)->count() : null,
                'out_of'        => $valid->count(),
                'class_average' => $valid->isNotEmpty() ? round($valid->avg(), 1) : null,
                'comments'      => $mineRow?->comments ?? $mineRow?->remarks ?? null,
            ];
        }

        $subjects = collect($subjects)->sortBy('name')->values()->all();

        $myTotal = $totals[$enrollment->id] ?? null;
        $scored  = collect($subjects)->filter(fn ($s) => $s['score'] !== null)->count();

        return [
            'enrollment'  => $enrollment,
            'subjects'    => $subjects,
            'total'       => $myTotal !== null ? round($myTotal, 1) : null,
            'average'     => ($myTotal !== null && $scored > 0) ? round($myTotal / $scored, 1) : null,
            'position'    => $myTotal !== null ? 1 + collect($totals)->filter(fn ($t) => $t > $myTotal)->count() : null,
            'stream_size' => count($totals),
        ];
    }

    /* ───────────── term helpers ───────────── */

    /**
     * The overview row carries no term id (pdf_url is just an opaque report id),
     * so the term is resolved from its label, e.g. "2026 Term 2".
     */
    private function resolveTerm(array $row, string $reportId): ?AcademicTerm
    {
        if (preg_match('/(\d{4})\s*term\s*(\d+)/i', (string) ($row['term_label'] ?? ''), $m)) {
            $term = AcademicTerm::where('academic_year', $m[1])
                ->where('term_number', (int) $m[2])
                ->first();

            if ($term) {
                return $term;
            }
        }

        return AcademicTerm::find($row['academic_term_id'] ?? $reportId);
    }

    /**
     * Collects every score for the given enrollments in a term.
     *
     * @return array{0: array<int|string, array<int|string, array{name:string, teacher:?string, rounds:array<string,float>}>>, 1: array<int,string>}
     *         [ $scores[enrollmentId][learningAreaId], $roundNamesInOrder ]
     */
    private function termScores(array $enrollmentIds, $termId): array
    {
        $assessments = Assessment::query()
            ->where('academic_term_id', $termId)
            ->whereHas('results', fn ($q) => $q->whereIn('student_enrollment_id', $enrollmentIds))
            ->with([
                'learningArea',
                'results' => fn ($q) => $q->whereIn('student_enrollment_id', $enrollmentIds),
            ])
            ->orderBy('id')
            ->get();

        $scores = [];
        $rounds = [];

        foreach ($assessments as $a) {
            $round  = (string) $a->name;
            $areaId = $a->learning_area_id ?? $a->learningArea?->id;

            if ($areaId === null) {
                continue;
            }

            if (! in_array($round, $rounds, true)) {
                $rounds[] = $round;
            }

            foreach ($a->results as $r) {
                if ($r->score === null) {
                    continue;
                }

                $scores[$r->student_enrollment_id][$areaId] ??= [
                    'name'    => $a->learningArea->name ?? '—',
                    'teacher' => $a->teacher->name ?? null,
                    'rounds'  => [],
                ];

                $scores[$r->student_enrollment_id][$areaId]['rounds'][$round] = (float) $r->score;
            }
        }

        return [$scores, $rounds];
    }

    /** e.g. "Mr. J. Kamau" / "Ms. A. Wanjiru" */
    private function teacherName($teacher): ?string
    {
        if (! $teacher) {
            return null;
        }

        return (ucwords((string) $teacher->gender) === 'Male' ? 'Mr.' : 'Ms.') . ' ' .
            strtoupper(substr((string) $teacher->first_name, 0, 1)) . '. ' .
            $teacher->last_name;
    }

    private function avg(array $values): ?float
    {
        return $values ? round(array_sum($values) / count($values), 1) : null;
    }

    /** Subject averages of the given enrollments for one learning area. */
    private function areaAverages(array $scores, array $enrollmentIds, $areaId): Collection
    {
        return collect($enrollmentIds)
            ->map(fn ($eid) => isset($scores[$eid][$areaId]) ? $this->avg($scores[$eid][$areaId]['rounds']) : null)
            ->filter(fn ($v) => $v !== null)
            ->values();
    }

    /** Sum of subject averages per enrollment (only enrollments that have results). */
    private function totals(array $scores, array $enrollmentIds): Collection
    {
        return collect($enrollmentIds)
            ->filter(fn ($eid) => ! empty($scores[$eid]))
            ->map(fn ($eid) => collect($scores[$eid])->sum(fn ($d) => $this->avg($d['rounds']) ?? 0))
            ->values();
    }

    /* ───────────── helpers ───────────── */

    private function output(string $html, string $filename, bool $landscape = false, ?string $title = null)
    {
        $mpdf = new Mpdf([
            'format'        => $landscape ? 'A4-L' : 'A4',
            'tempDir'       => storage_path('app/mpdf'),
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 8,
            'margin_bottom' => 14,
        ]);

        if ($title) {
            $mpdf->SetTitle($title);
        }
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }

    private function resolveImageBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // The layout serves the logo through route('file'), so it may live in storage rather than the base path.
        $candidates = [
            base_path($path),
            storage_path('app/public/' . ltrim($path, '/')),
            storage_path('app/' . ltrim($path, '/')),
            public_path($path),
        ];

        foreach ($candidates as $fullPath) {
            if (is_file($fullPath)) {
                return 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }

        \Log::info('Logo file not found', ['path' => $path, 'tried' => $candidates]);

        return null;
    }
}
