<?php


namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PaymentSchedule;
use App\Models\PaymentScheduleLine;
use App\Models\StaffPayee;
use App\Services\Payroll\PayrollDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayrollDocumentController extends Controller
{
    public function __construct(private PayrollDocumentService $docs)
    {
    }

    public function payslip(PaymentSchedule $schedule, PaymentScheduleLine $line)
    {
        $this->ensureFinal($schedule);
        abort_unless($line->payment_schedule_id === $schedule->id, 404);

        $slip = $this->docs->payslip($schedule, $line);

        return $this->pdf(
            view('payroll.docs.payslips', ['slips' => [$slip]])->render(),
            'payslip-' . Str::slug($line->full_name) . '-' . $schedule->period->format('Y-m'),
            false,
            $slip['brand']['name'] . ' · Confidential — computer-generated payslip'
        );
    }

    public function payslips(PaymentSchedule $schedule)
    {
        $this->ensureFinal($schedule);
        $schedule->load('lines.items');
        abort_if($schedule->lines->isEmpty(), 404, 'No lines on this schedule.');

        $slips = $schedule->lines->map(fn($l) => $this->docs->payslip($schedule, $l))->all();

        return $this->pdf(
            view('payroll.docs.payslips', compact('slips'))->render(),
            'payslips-' . $schedule->period->format('Y-m'),
            false,
            $slips[0]['brand']['name'] . ' · Confidential — computer-generated payslip'
        );
    }

    public function p9Index(Request $request)
    {
        $years = $this->docs->years();
        $year = (int)$request->get('year', $years[0]);
        $rows = $this->docs->p9Summary($year);

        return view('payroll.p9.index', compact('years', 'year', 'rows'));
    }

    public function p9(Request $request, StaffPayee $payee)
    {
        $year = (int)$request->get('year', now()->year);
        $data = $this->docs->p9($payee, $year);
        abort_unless($data['hasData'], 404, "No approved payroll for {$payee->full_name} in {$year}.");

        return $this->pdf(
            view('payroll.docs.p9s', ['cards' => [$data]])->render(),
            'p9-' . Str::slug($payee->full_name) . '-' . $year,
            true,
            $data['brand']['name'] . ' · Tax deduction card ' . $year . ' · Confidential'
        );
    }

    public function p9All(Request $request)
    {
        $year = (int)$request->get('year', now()->year);
        $cards = $this->docs->p9PayeeIds($year)
            ->map(fn($id) => $this->docs->p9(StaffPayee::withTrashed()->find($id), $year))
            ->filter(fn($d) => $d['hasData'])->values()->all();
        abort_if(empty($cards), 404, "No approved payroll for {$year}.");

        return $this->pdf(
            view('payroll.docs.p9s', compact('cards'))->render(),
            'p9-all-' . $year,
            true,
            $cards[0]['brand']['name'] . ' · Tax deduction cards ' . $year . ' · Confidential'
        );
    }

    private function ensureFinal(PaymentSchedule $schedule): void
    {
        abort_unless(in_array($schedule->status, ['approved', 'paid'], true), 403, 'Payslips are available only after the schedule is approved.');
    }

    private function pdf(string $html, string $filename, bool $landscape, string $footer)
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
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }
}
