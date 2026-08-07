<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Stream;
use App\Models\StudentEnrollment;
use App\Models\TermOverallResult;
use App\Services\Results\AssessmentRoundReportBuilder;
use App\Services\Results\TermSubjectReportBuilder;
use App\Services\Results\YearReportBuilder;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class ReportCardPdfController extends Controller
{
    /** ---------- TERM REPORT ---------- */

    public function show(TermOverallResult $termOverallResult, TermSubjectReportBuilder $builder)
    {
        $user = request()->user();
        $enrollment = $termOverallResult->enrollment()->with('student', 'gradeLevel', 'stream')->first();

        $isOwner = $enrollment->user_id === $user->id;
        $isStaff = $user->hasPermission('results.view') || $user->hasPermission('curriculum.manage');
        abort_unless($isStaff || ($isOwner && $termOverallResult->status === 'published'), 403);

        $academicTerm = $termOverallResult->academicTerm;
        $built = $builder->build($enrollment->stream, $academicTerm);

        $html = view('results.report-cards.pdf', [
            'enrollment'  => $enrollment,
            'overall'     => $termOverallResult,
            'subjects'    => $built['students'][$enrollment->id] ?? [],
            'rounds'      => $built['rounds'],
            'schoolName'  => setting('school_name', config('app.name')),
            'schoolPhone' => setting('school_phone'),
            'logoPath'    => $this->resolveImageBase64(setting('logo_path')),
        ])->render();

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_left'   => 10,
            'margin_right'  => 5,
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'margin_header' => 8,        // space reserved for header (if using SetHTMLHeader)
            'margin_footer' => 8,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('report-card-' . $enrollment->student->userID . '.pdf', 'I');
    }

    public function showBulk(Request $request, Stream $stream, AcademicTerm $academicTerm, TermSubjectReportBuilder $builder)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('results.view') || $user->hasPermission('curriculum.manage'), 403);

        $results = TermOverallResult::query()
            ->whereHas('enrollment', fn ($q) => $q->where('stream_id', $stream->id))
            ->where('academic_term_id', $academicTerm->id)
            ->with('enrollment.student', 'enrollment.stream')
            ->orderBy('position_in_stream')
            ->get();

        $selected = $request->query('results');
        if (! empty($selected)) {
            $results = $results->filter(fn ($r) => in_array($r->id, $selected));
        }

        abort_if($results->isEmpty(), 404);

        $built = $builder->build($stream, $academicTerm);
        $schoolName = setting('school_name', config('app.name'));
        $schoolPhone = setting('school_phone');
        $logoPath = $this->resolveImageBase64(setting('logo_path'));
        $rounds = $built['rounds'];

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_left'   => 10,
            'margin_right'  => 5,
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'margin_header' => 8,        // space reserved for header (if using SetHTMLHeader)
            'margin_footer' => 8,
            ]);

        foreach ($results as $index => $overall) {
            $enrollment = $overall->enrollment;
            $subjects = $built['students'][$enrollment->id] ?? [];
            $html = view('results.report-cards.pdf', compact('enrollment', 'overall', 'subjects', 'rounds', 'schoolName', 'schoolPhone', 'logoPath'))->render();
            if ($index > 0) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML($html);
        }

        return $mpdf->Output($stream->full_name . '-' . $academicTerm->academic_year . '-T' . $academicTerm->term_number . '-batch.pdf', 'I');
    }

    /** ---------- SINGLE-ROUND (EXAM) REPORT ---------- */

    public function showAssessment(Stream $stream, AcademicTerm $academicTerm, string $name, StudentEnrollment $studentEnrollment, AssessmentRoundReportBuilder $builder)
    {
        $name = urldecode($name);
        $user = request()->user();
        abort_unless($user->hasPermission('my-results.view') || $user->hasPermission('results.view') || $user->hasPermission('curriculum.manage'), 403);

        $students = collect($builder->build($stream, $academicTerm, $name))->keyBy(fn ($s) => $s['enrollment']->id);
        abort_unless($students->has($studentEnrollment->id), 404);


        $html = view('results.report-cards.assessment-pdf', [
            'stream' => $stream, 'academicTerm' => $academicTerm, 'name' => $name,
            'row' => $students[$studentEnrollment->id],
            'schoolName' => setting('school_name', config('app.name')),
            'schoolPhone' => setting('school_phone'),
            'logoPath' => $this->resolveImageBase64(setting('logo_path')),
        ])->render();

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_left'   => 10,
            'margin_right'  => 5,
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'margin_header' => 8,        // space reserved for header (if using SetHTMLHeader)
            'margin_footer' => 8,
            ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output($name . '-' . $studentEnrollment->student->userID . '.pdf', 'I');
    }

    public function showAssessmentBulk(Request $request, Stream $stream, AcademicTerm $academicTerm, string $name, AssessmentRoundReportBuilder $builder)
    {
        $name = urldecode($name);
        $user = $request->user();
        abort_unless($user->hasPermission('results.view') || $user->hasPermission('curriculum.manage'), 403);

        $students = collect($builder->build($stream, $academicTerm, $name));

        $selected = $request->query('enrollments');
        if (! empty($selected)) {
            $students = $students->filter(fn ($s) => in_array($s['enrollment']->id, $selected));
        }

        abort_if($students->isEmpty(), 404);

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_left'   => 10,
            'margin_right'  => 5,
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'margin_header' => 8,        // space reserved for header (if using SetHTMLHeader)
            'margin_footer' => 8,
            ]);

        $schoolName = setting('school_name', config('app.name'));
        $schoolPhone = setting('school_phone');
        $logoPath = $this->resolveImageBase64(setting('logo_path'));

        foreach ($students as $index => $row) {
            $html = view('results.report-cards.assessment-pdf', compact('stream', 'academicTerm', 'name', 'row', 'schoolName', 'schoolPhone', 'logoPath'))->render();
            if ($index > 0) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML($html);
        }

        return $mpdf->Output($name . '-' . $stream->full_name . '-batch.pdf', 'I');
    }

    /** ---------- YEAR REPORT ---------- */

    public function showYear(Stream $stream, string $academicYear, StudentEnrollment $studentEnrollment, YearReportBuilder $builder)
    {
        $user = request()->user();
        abort_unless($user->hasPermission('results.view') || $user->hasPermission('curriculum.manage') || $user->hasPermission('my-results.view'), 403);

        $students = collect($builder->build($stream, $academicYear))->keyBy(fn ($s) => $s['enrollment']->id);
        abort_unless($students->has($studentEnrollment->id), 404);

        $html = view('results.report-cards.year-pdf', [
            'stream' => $stream, 'academicYear' => $academicYear,
            'row' => $students[$studentEnrollment->id],
            'schoolName' => setting('school_name', config('app.name')),
            'schoolPhone' => setting('school_phone'),
            'logoPath' => $this->resolveImageBase64(setting('logo_path')),
        ])->render();

        $mpdf = new Mpdf(['format' => 'A4']);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('year-report-' . $studentEnrollment->student->userID . '.pdf', 'I');
    }

    public function showYearBulk(Request $request, Stream $stream, string $academicYear, YearReportBuilder $builder)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('results.view') || $user->hasPermission('curriculum.manage'), 403);

        $students = collect($builder->build($stream, $academicYear));

        $selected = $request->query('enrollments');
        if (! empty($selected)) {
            $students = $students->filter(fn ($s) => in_array($s['enrollment']->id, $selected));
        }

        abort_if($students->isEmpty(), 404);

        $mpdf = new Mpdf(['format' => 'A4']);
        $schoolName = setting('school_name', config('app.name'));
        $schoolPhone = setting('school_phone');
        $logoPath = $this->resolveImageBase64(setting('logo_path'));

        foreach ($students as $index => $row) {
            $html = view('results.report-cards.year-pdf', compact('stream', 'academicYear', 'row', 'schoolName', 'schoolPhone', 'logoPath'))->render();
            if ($index > 0) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML($html);
        }

        return $mpdf->Output($stream->full_name . '-' . $academicYear . '-year-batch.pdf', 'I');
    }

    /** ---------- SHARED ---------- */

    /**
     * Converts a stored logo path (relative to base_path, matching your
     * base_path('Files/images/') convention) into a base64 data URI, since
     * mpdf can't reliably fetch images via HTTP from within the PDF render.
     */
    private function resolveImageBase64(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $fullPath = base_path($path);
        if (! file_exists($fullPath)) {
            return null;
        }

        $type = pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'png';

        return 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($fullPath));
    }
}
