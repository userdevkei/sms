<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PaymentSchedule;
use App\Models\PaymentScheduleLineItem;
use App\Services\Payroll\PaymentScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class PaymentScheduleController extends Controller
{
    public function __construct(private PaymentScheduleService $service) {}

    public function index()
    {
        $schedules = PaymentSchedule::orderByDesc('period')->orderByDesc('created_at')->get();

        return view('payroll.schedules.index', compact('schedules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'period' => 'required|date_format:Y-m',
            'title'  => 'nullable|string|max:255',
        ]);

        $period = Carbon::createFromFormat('Y-m', $data['period'])->startOfMonth();

        $schedule = PaymentSchedule::create([
            'title'       => $data['title'] ?: 'Staff Payment Schedule – ' . $period->format('F Y'),
            'period'      => $period,
            'prepared_by' => auth()->id(),
        ]);
        $this->service->generate($schedule);

        return redirect()->route('payroll.schedules.show', $schedule)->with('success', 'Schedule generated. Review the lines before approval.');
    }

    public function show(PaymentSchedule $schedule)
    {
        $schedule->load('lines.items');
        $missing = $schedule->lines->filter(fn ($l) => blank($l->kra_pin) || blank($l->nssf_no) || blank($l->shif_no)
            || ($l->payment_method === 'bank' && blank($l->bank_account))
            || ($l->payment_method === 'mpesa' && blank($l->mpesa_phone)))->count();

        return view('payroll.schedules.show', compact('schedule', 'missing'));
    }

    /** Draft only: save days worked + per-line adjustment items, then recompute (server is authoritative). */
    public function updateLines(Request $request, PaymentSchedule $schedule)
    {
        abort_unless($schedule->isDraft(), 422, 'Schedule is locked.');

        // drop fully blank item rows, keep partial ones so they get validated
        $request->merge(['lines' => collect($request->input('lines', []))->map(function ($l) {
            $l['items'] = collect($l['items'] ?? [])
                ->filter(fn ($i) => filled($i['name'] ?? null) || filled($i['amount'] ?? null))
                ->values()->all();

            return $l;
        })->all()]);

        $request->validate([
            'lines'                    => 'required|array',
            'lines.*.days_worked'      => 'required|integer|min:0|max:31',
            'lines.*.items'            => 'nullable|array',
            'lines.*.items.*.kind'     => ['required', Rule::in(array_keys(PaymentScheduleLineItem::KINDS))],
            'lines.*.items.*.name'     => 'required|string|max:255',
            'lines.*.items.*.amount'   => 'required|numeric|min:0.01',
        ], [
            'lines.*.items.*.name.required'   => 'Every adjustment needs a description.',
            'lines.*.items.*.amount.required' => 'Every adjustment needs an amount.',
            'lines.*.items.*.amount.min'      => 'Adjustment amounts must be greater than 0.',
        ]);

        DB::transaction(function () use ($request, $schedule) {
            foreach ($schedule->lines as $line) {
                $in = $request->input("lines.{$line->id}");
                if (! $in) {
                    continue;
                }

                $items = collect($in['items'] ?? []);

                $line->items()->delete();
                foreach ($items as $i) {
                    $line->items()->create(['kind' => $i['kind'], 'name' => $i['name'], 'amount' => $i['amount']]);
                }

                $sum = fn (string $k) => round((float) $items->where('kind', $k)->sum('amount'), 2);

                $line->days_worked           = min((int) $in['days_worked'], $line->days_in_month);
                $line->one_off_allowance     = $sum('allowance');
                $line->one_off_reimbursement = $sum('reimbursement');
                $line->one_off_deduction     = $sum('deduction');

                $this->service->recalculate($line);
                $line->save();
            }
            $this->service->refreshTotals($schedule);
        });

        return back()->with('success', 'Schedule saved and recalculated.');
    }

    public function regenerate(PaymentSchedule $schedule)
    {
        $this->service->generate($schedule);

        return back()->with('success', 'Schedule rebuilt from the payee register (one-off edits were reset).');
    }

    public function approve(PaymentSchedule $schedule)
    {
        abort_unless($schedule->isDraft(), 422);
        abort_if($schedule->lines()->count() === 0, 422, 'Nothing to approve.');
        abort_if(
            config('kenya_payroll.require_separate_approver') && $schedule->prepared_by === auth()->id(),
            403,
            'A different user must approve this schedule.'
        );

        $schedule->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        return back()->with('success', 'Schedule approved and locked.');
    }

    public function markPaid(Request $request, PaymentSchedule $schedule)
    {
        abort_unless($schedule->status === 'approved', 422);
        $data = $request->validate(['paid_on' => 'required|date', 'payment_reference' => 'nullable|string|max:255']);
        $schedule->update($data + ['status' => 'paid']);

        return back()->with('success', 'Marked as paid.');
    }

    public function destroy(PaymentSchedule $schedule)
    {
        abort_unless($schedule->isDraft(), 422, 'Only drafts can be deleted.');
        $schedule->lines()->forceDelete();
        $schedule->forceDelete();

        return redirect()->route('payroll.schedules.index')->with('success', 'Draft deleted.');
    }

    public function pdf(PaymentSchedule $schedule)
    {
        $schedule->load('lines');
        $logoPath = $this->resolveImageBase64(setting('logo_path'));
        $html = view('payroll.schedules.pdf', compact('schedule', 'logoPath'))->render();

        $mpdf = new \Mpdf\Mpdf([
            'format'  => 'A4-L',
            'tempDir' => storage_path('app/mpdf'),
            'margin_left' => 8, 'margin_right' => 8, 'margin_top' => 10, 'margin_bottom' => 12,
        ]);
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="payment-schedule-' . $schedule->period->format('Y-m') . '.pdf"',
        ]);
    }

    private function resolveImageBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = base_path($path); // since path already starts with "Files/..."

        if (! file_exists($fullPath)) {
            \Log::info('Logo file not found', ['path' => $fullPath]);
            return null;
        }

        $mime = mime_content_type($fullPath);
        $data = file_get_contents($fullPath);

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    public function excel(PaymentSchedule $schedule)
    {
        $schedule->load('lines');
        $book  = new Spreadsheet();
        $money = '#,##0.00;[Red]-#,##0.00;"-"';

        // Palette
        $navy   = '1F3864';
        $blue   = '2F5597';
        $soft   = 'D9E2F3';
        $band   = 'F5F8FC';
        $line   = 'D0D7E2';
        $white  = 'FFFFFF';

        // Reusable styling helpers
        $banner = function ($sheet, string $lastCol, string $title, string $subtitle) use ($navy, $soft, $white) {
            $sheet->mergeCells("A1:{$lastCol}1")->mergeCells("A2:{$lastCol}2");
            $sheet->setCellValue('A1', $title)->setCellValue('A2', $subtitle);
            $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => $white], 'name' => 'Calibri'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $navy]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            ]);
            $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '44546A']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $soft]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(32);
            $sheet->getRowDimension(2)->setRowHeight(20);
            $sheet->getRowDimension(3)->setRowHeight(8);
        };

        $header = function ($sheet, string $range, int $row) use ($blue, $white) {
            $sheet->getStyle($range)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => $white], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $blue]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $white]]],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(26);
        };

        $body = function ($sheet, string $range, int $first, int $last, string $lastCol) use ($band, $line) {
            $sheet->getStyle($range)->applyFromArray([
                'font'    => ['size' => 10],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $line]]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            for ($i = $first; $i <= $last; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(19);
                if (($i - $first) % 2 === 1) {
                    $sheet->getStyle("A{$i}:{$lastCol}{$i}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($band);
                }
            }
        };

        $totals = function ($sheet, string $range, int $row) use ($soft, $navy) {
            $sheet->getStyle($range)->applyFromArray([
                'font'    => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $navy]],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $soft]],
                'borders' => [
                    'top'    => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => $navy]],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => $navy]],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(22);
        };

        $print = function ($sheet, int $headerRow, string $lastCol, int $lastRow) {
            $sheet->setShowGridlines(false);
            $sheet->getPageSetup()
                ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                ->setPaperSize(PageSetup::PAPERSIZE_A4)
                ->setFitToPage(true)
                ->setFitToWidth(1)
                ->setFitToHeight(0)
                ->setRowsToRepeatAtTopByStartAndEnd(1, $headerRow)
                ->setPrintArea("A1:{$lastCol}{$lastRow}");
            $sheet->getPageMargins()->setTop(0.6)->setBottom(0.7)->setLeft(0.4)->setRight(0.4);
            $sheet->getPageSetup()->setHorizontalCentered(true);
            $sheet->getHeaderFooter()->setOddFooter('&L&8Confidential – Payroll Data&C&8Page &P of &N&R&8Generated &D');
        };

        $widths = function ($sheet, array $map) {
            foreach ($map as $col => $w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }
        };

        $book->getProperties()
            ->setCreator(config('app.name', 'Payroll'))
            ->setTitle($schedule->title)
            ->setSubject('Staff Payment Schedule')
            ->setCompany(config('app.name', ''));

        /* ───────────── Sheet 1 – Payment Schedule ───────────── */
        $s = $book->getActiveSheet()->setTitle('Payment Schedule');
        $s->getTabColor()->setRGB($navy);

        $banner(
            $s, 'O', $schedule->title,
            'Period: ' . $schedule->period->format('F Y') . '   |   Status: ' . ucfirst($schedule->status)
        );

        $head = ['#', 'Staff No', 'Name', 'KRA PIN', 'Gross', 'NSSF', 'SHIF', 'Housing Levy', 'PAYE', 'Other Ded.', 'Non-taxable', 'Net Pay', 'Method', 'Bank / Phone', 'Account'];
        $s->fromArray($head, null, 'A4');
        $header($s, 'A4:O4', 4);

        $r = 5;
        foreach ($schedule->lines as $i => $l) {
            $s->fromArray([
                $i + 1, $l->staff_no, $l->full_name, null, $l->gross_pay, $l->nssf, $l->shif, $l->housing_levy, $l->paye,
                $l->other_deductions + $l->one_off_deduction + $l->pension, $l->non_taxable_allowances, $l->net_pay,
                strtoupper($l->payment_method), null, null,
            ], null, "A{$r}");

            // Text-typed cells so long numbers are never converted to scientific notation
            $s->setCellValueExplicit("D{$r}", (string) $l->kra_pin, DataType::TYPE_STRING);
            $s->setCellValueExplicit(
                "N{$r}",
                (string) ($l->payment_method === 'mpesa' ? $l->mpesa_phone : $l->bank_name),
                DataType::TYPE_STRING
            );
            $s->setCellValueExplicit("O{$r}", (string) $l->bank_account, DataType::TYPE_STRING);
            $r++;
        }

        $first = 5;
        $last  = max($r - 1, $first);
        $body($s, "A{$first}:O{$last}", $first, $last, 'O');

        // Totals row
        $s->setCellValue("C{$r}", 'TOTAL');
        foreach (['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'] as $col) {
            $s->setCellValue("{$col}{$r}", "=SUM({$col}{$first}:{$col}{$last})");
        }
        $totals($s, "A{$r}:O{$r}", $r);

        // Alignment & number formats
        $s->getStyle("E{$first}:L{$r}")->getNumberFormat()->setFormatCode($money);
        $s->getStyle("E{$first}:L{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $s->getStyle("A{$first}:B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle("M{$first}:M{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle("C{$first}:D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
        $s->getStyle("N{$first}:O{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
        $s->getStyle("L{$first}:L{$r}")->getFont()->setBold(true);
        $s->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Sign-off block
        $sig = $r + 4;
        foreach ([['B', 'D', 'Prepared by'], ['F', 'I', 'Reviewed by'], ['K', 'N', 'Approved by']] as [$from, $to, $label]) {
            $s->getStyle("{$from}{$sig}:{$to}{$sig}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('7F7F7F');
            $s->mergeCells("{$from}" . ($sig + 1) . ":{$to}" . ($sig + 1));
            $s->setCellValue("{$from}" . ($sig + 1), "{$label}  (Name / Signature / Date)");
            $s->getStyle("{$from}" . ($sig + 1))->applyFromArray([
                'font'      => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '595959']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        $widths($s, [
            'A' => 5,  'B' => 11, 'C' => 28, 'D' => 15, 'E' => 14, 'F' => 13, 'G' => 13, 'H' => 14,
            'I' => 14, 'J' => 13, 'K' => 14, 'L' => 15, 'M' => 10, 'N' => 18, 'O' => 20,
        ]);
        $s->freezePane('D5');
        $s->setAutoFilter("A4:O{$last}");
        $print($s, 4, 'O', $sig + 1);

        /* ───────────── Sheet 2 – Statutory Remittance ───────────── */
        $t = $book->createSheet()->setTitle('Statutory Remittance');
        $t->getTabColor()->setRGB($blue);

        $banner(
            $t, 'M', 'Statutory Remittances – KRA PAYE · NSSF · SHA · AHL · NITA',
            'Period: ' . $schedule->period->format('F Y') . '   |   Due by: ' . $schedule->remittanceDue()->format('d M Y')
        );

        $t->fromArray(['KRA PIN', 'Name', 'NSSF No', 'SHIF No', 'Gross', 'Taxable Pay', 'PAYE', 'NSSF (Emp)', 'NSSF (Er)', 'SHIF', 'AHL (Emp)', 'AHL (Er)', 'NITA'], null, 'A4');
        $header($t, 'A4:M4', 4);

        $r = 5;
        foreach ($schedule->lines as $l) {
            $t->fromArray([
                null, $l->full_name, null, null, $l->gross_pay, $l->taxable_pay, $l->paye, $l->nssf,
                $l->employer_nssf, $l->shif, $l->housing_levy, $l->employer_housing_levy, $l->nita,
            ], null, "A{$r}");
            $t->setCellValueExplicit("A{$r}", (string) $l->kra_pin, DataType::TYPE_STRING);
            $t->setCellValueExplicit("C{$r}", (string) $l->nssf_no, DataType::TYPE_STRING);
            $t->setCellValueExplicit("D{$r}", (string) $l->shif_no, DataType::TYPE_STRING);
            $r++;
        }

        $first = 5;
        $last  = max($r - 1, $first);
        $body($t, "A{$first}:M{$last}", $first, $last, 'M');

        $t->setCellValue("B{$r}", 'TOTAL');
        foreach (range('E', 'M') as $col) {
            $t->setCellValue("{$col}{$r}", "=SUM({$col}{$first}:{$col}{$last})");
        }
        $totals($t, "A{$r}:M{$r}", $r);

        $t->getStyle("E{$first}:M{$r}")->getNumberFormat()->setFormatCode($money);
        $t->getStyle("E{$first}:M{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $t->getStyle("A{$first}:D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
        $t->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $widths($t, [
            'A' => 15, 'B' => 28, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 14, 'G' => 13,
            'H' => 13, 'I' => 13, 'J' => 13, 'K' => 13, 'L' => 13, 'M' => 11,
        ]);
        $t->freezePane('C5');
        $t->setAutoFilter("A4:M{$last}");
        $print($t, 4, 'M', $r);

        $book->setActiveSheetIndex(0);
        $name = 'payment-schedule-' . $schedule->period->format('Y-m') . '.xlsx';

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
