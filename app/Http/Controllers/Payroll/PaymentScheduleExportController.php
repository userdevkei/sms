<?php


namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PaymentSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PaymentScheduleExportController extends Controller
{
    public function __invoke(Request $request)
    {
        // The page sends the ids of the rows currently visible after filtering
        $ids = collect(explode(',', (string)$request->query('ids')))
            ->map(fn($v) => (int)$v)->filter()->unique()->values();

        $schedules = PaymentSchedule::withCount('lines')
            ->when($ids->isNotEmpty(), fn($q) => $q->whereIn('id', $ids))
            ->orderBy('period')->orderBy('created_at')
            ->get();

        $book = new Spreadsheet();
        $money = '#,##0.00;[Red]-#,##0.00;"-"';

        // Palette
        $navy = '1F3864';
        $blue = '2F5597';
        $soft = 'D9E2F3';
        $band = 'F5F8FC';
        $line = 'D0D7E2';
        $white = 'FFFFFF';

        $school = setting('school_name') ?: config('app.name');
        $lastCol = 'M';
        $headerRow = 6;
        $first = 7;

        $book->getProperties()
            ->setCreator($school)
            ->setTitle('Staff Payment Schedules')
            ->setSubject('Payment schedules register')
            ->setCompany($school);

        $s = $book->getActiveSheet()->setTitle('Payment Schedules');
        $s->getTabColor()->setRGB($navy);
        $s->setShowGridlines(false);

        /* ── Branding block ── */
        $hasLogo = false;
        $logoFile = setting('logo_path') ? base_path(setting('logo_path')) : null;
        if ($logoFile && is_file($logoFile)) {
            try {
                $d = new Drawing();
                $d->setName('Logo')->setPath($logoFile)->setCoordinates('A1')
                    ->setOffsetX(6)->setOffsetY(4)->setHeight(46);
                if ($d->getWidth() > 220) {
                    $d->setWidth(220);
                }
                $d->setWorksheet($s);
                $hasLogo = true;
            } catch (\Throwable $e) {
                $hasLogo = false; // unsupported image type (e.g. SVG): continue without logo
            }
        }

        $tc = $hasLogo ? 'D' : 'A';
        $s->mergeCells("{$tc}1:{$lastCol}1")->mergeCells("{$tc}2:{$lastCol}2")->mergeCells("A4:{$lastCol}4");
        $s->setCellValue("{$tc}1", $school)->setCellValue("{$tc}2", 'STAFF PAYMENT SCHEDULES REGISTER');
        $s->getStyle("{$tc}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => $navy]],
            'alignment' => ['vertical' => Alignment::VERTICAL_BOTTOM],
        ]);
        $s->getStyle("{$tc}2")->applyFromArray([
            'font' => ['size' => 9, 'bold' => true, 'color' => ['rgb' => '64748B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
        ]);
        $s->getRowDimension(1)->setRowHeight(32);
        $s->getRowDimension(2)->setRowHeight(22);

        // Navy rule
        $s->getStyle("A3:{$lastCol}3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navy);
        $s->getRowDimension(3)->setRowHeight(4);

        // Meta line
        $s->setCellValue('A4', 'Generated ' . now()->format('d M Y, H:i') . '   |   ' . $schedules->count() . ' schedule(s)   |   Confidential – Payroll Data');
        $s->getStyle('A4')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $s->getRowDimension(4)->setRowHeight(20);
        $s->getRowDimension(5)->setRowHeight(6);

        /* ── Table header ── */
        $head = ['#', 'Period', 'Title', 'Status', 'Staff', 'Gross', 'NSSF', 'SHIF', 'AHL', 'PAYE', 'Net Pay', 'Paid On', 'Reference'];
        $s->fromArray($head, null, "A{$headerRow}");
        $s->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $white], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $blue]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $white]]],
        ]);
        $s->getRowDimension($headerRow)->setRowHeight(26);

        /* ── Rows ── */
        $statusColor = ['draft' => '64748B', 'approved' => '1D4ED8', 'paid' => '15803D'];
        $r = $first;
        foreach ($schedules as $i => $x) {
            $s->setCellValue("A{$r}", $i + 1);
            $s->setCellValue("B{$r}", Date::PHPToExcel($x->period));
            $s->setCellValue("C{$r}", $x->title);
            $s->setCellValue("D{$r}", ucfirst($x->status));
            $s->setCellValue("E{$r}", (int)$x->lines_count);
            $s->setCellValue("F{$r}", (float)$x->total_gross);
            $s->setCellValue("G{$r}", (float)$x->total_nssf);
            $s->setCellValue("H{$r}", (float)$x->total_shif);
            $s->setCellValue("I{$r}", (float)$x->total_housing_levy);
            $s->setCellValue("J{$r}", (float)$x->total_paye);
            $s->setCellValue("K{$r}", (float)$x->total_net);
            if ($x->paid_on) {
                $s->setCellValue("L{$r}", Date::PHPToExcel(Carbon::parse($x->paid_on)));
            }
            $s->setCellValueExplicit("M{$r}", (string)$x->payment_reference, DataType::TYPE_STRING);

            $s->getStyle("D{$r}")->getFont()->setBold(true)
                ->getColor()->setRGB($statusColor[$x->status] ?? '334155');
            $r++;
        }

        $last = max($r - 1, $first);

        // Body styling
        $s->getStyle("A{$first}:{$lastCol}{$last}")->applyFromArray([
            'font' => ['size' => 10],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $line]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        for ($i = $first; $i <= $last; $i++) {
            $s->getRowDimension($i)->setRowHeight(20);
            if (($i - $first) % 2 === 1) {
                $s->getStyle("A{$i}:{$lastCol}{$i}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($band);
            }
        }

        // Totals row
        $s->setCellValue("C{$r}", 'TOTAL');
        foreach (['F', 'G', 'H', 'I', 'J', 'K'] as $col) {
            $s->setCellValue("{$col}{$r}", "=SUM({$col}{$first}:{$col}{$last})");
        }
        $s->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $navy]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $soft]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $navy]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => $navy]],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $s->getRowDimension($r)->setRowHeight(24);

        // Formats & alignment
        $s->getStyle("B{$first}:B{$last}")->getNumberFormat()->setFormatCode('mmm yyyy');
        $s->getStyle("L{$first}:L{$last}")->getNumberFormat()->setFormatCode('dd mmm yyyy');
        $s->getStyle("F{$first}:K{$r}")->getNumberFormat()->setFormatCode($money);
        $s->getStyle("F{$first}:K{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $s->getStyle("K{$first}:K{$r}")->getFont()->setBold(true);
        $s->getStyle("A{$first}:B{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle("D{$first}:E{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle("L{$first}:L{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle("C{$first}:C{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
        $s->getStyle("M{$first}:M{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
        $s->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Column widths
        foreach ([
                     'A' => 5, 'B' => 12, 'C' => 38, 'D' => 12, 'E' => 8, 'F' => 15, 'G' => 13,
                     'H' => 13, 'I' => 13, 'J' => 14, 'K' => 16, 'L' => 13, 'M' => 22,
                 ] as $col => $w) {
            $s->getColumnDimension($col)->setWidth($w);
        }

        // Navigation & printing
        $s->freezePane("D{$first}");
        $s->setAutoFilter("A{$headerRow}:{$lastCol}{$last}");
        $s->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow)
            ->setPrintArea("A1:{$lastCol}{$r}")
            ->setHorizontalCentered(true);
        $s->getPageMargins()->setTop(0.6)->setBottom(0.7)->setLeft(0.4)->setRight(0.4);
        $s->getHeaderFooter()->setOddFooter('&L&8Confidential – Payroll Data&C&8Page &P of &N&R&8' . $school);

        $book->setActiveSheetIndex(0);
        $name = 'payment-schedules-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
