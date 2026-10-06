<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PaymentScheduleLine;
use App\Models\StaffPayee;
use App\Services\Payroll\PayrollDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MyPayrollController extends Controller
{
    /** Schedule statuses staff may see. Use ['paid'] to release documents only after payment. */
    private const VISIBLE = ['approved', 'paid'];

    public function __construct(private PayrollDocumentService $docs)
    {
    }

    public function index(Request $request)
    {
        $payee = $this->payee();
        $all = collect();

        if ($payee) {
            $all = PaymentScheduleLine::with('schedule')
                ->where('staff_payee_id', $payee->id)
                ->whereHas('schedule', fn($q) => $q->whereIn('status', self::VISIBLE))
                ->get()
                ->sortByDesc(fn($l) => $l->schedule->period)
                ->values();
        }

        $years = $all->map(fn($l) => $l->schedule->period->year)->unique()->values();
        $year = (int)$request->get('year', $years->first() ?? now()->year);
        if ($years->isNotEmpty() && !$years->contains($year)) {
            $year = (int)$years->first();
        }

        $lines = $all->filter(fn($l) => $l->schedule->period->year === $year)->values();

        $stats = [
            'latest' => $all->first(),
            'gross' => $lines->sum('gross_pay'),
            'paye' => $lines->sum('paye'),
            'net' => $lines->sum('net_pay'),
        ];

        $p9Rows = $all->groupBy(fn($l) => $l->schedule->period->year)->map(fn($g, $y) => [
            'year' => (int)$y,
            'months' => $g->map(fn($l) => $l->schedule->period->format('m'))->unique()->count(),
            'gross' => $g->sum('gross_pay'),
            'paye' => $g->sum('paye'),
        ])->sortByDesc('year')->values();

        return view('payroll.my.index', compact('payee', 'lines', 'years', 'year', 'stats', 'p9Rows'));
    }

    public function payslip(Request $request, PaymentScheduleLine $line)
    {
        $payee = $this->payee();
        abort_unless($payee && $line->staff_payee_id === $payee->id, 404);

        $schedule = $line->schedule;
        abort_unless($schedule && in_array($schedule->status, self::VISIBLE, true), 404);

        $slip = $this->docs->payslip($schedule, $line);

        return $this->pdf(
            view('payroll.docs.payslips', ['slips' => [$slip]])->render(),
            'payslip-' . Str::slug($line->full_name) . '-' . $schedule->period->format('Y-m'),
            false,
            $slip['brand']['name'] . ' · Confidential — computer-generated payslip',
            $request->boolean('download')
        );
    }

    public function p9(Request $request, int $year)
    {
        $payee = $this->payee();
        abort_unless($payee, 404);

        $data = $this->docs->p9($payee, $year);
        abort_unless($data['hasData'], 404);

        return $this->pdf(
            view('payroll.docs.p9s', ['cards' => [$data]])->render(),
            'p9-' . Str::slug($payee->full_name) . '-' . $year,
            true,
            $data['brand']['name'] . ' · Tax deduction card ' . $year . ' · Confidential',
            $request->boolean('download')
        );
    }

    private function payee(): ?StaffPayee
    {
        return StaffPayee::withTrashed()->where('user_id', auth()->id())->first();
    }

    private function pdf(string $html, string $filename, bool $landscape, string $footer, bool $download)
    {
        $mpdf = new \Mpdf\Mpdf([
            'format' => $landscape ? 'A4-L' : 'A4',
            'tempDir' => storage_path('app/mpdf'),
            'margin_left' => $landscape ? 8 : 12,
            'margin_right' => $landscape ? 8 : 12,
            'margin_top' => $landscape ? 8 : 10,
            'margin_bottom' => 14,
            'margin_footer' => 5,
        ]);
        $mpdf->SetTitle($filename);
        $mpdf->SetFooter($footer . '||Generated ' . now()->format('d M Y H:i') . ($landscape ? ' · Page {PAGENO}/{nbpg}' : ''));
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '.pdf"',
        ]);
    }
}
