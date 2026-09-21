<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DebtorsExcelExport
{
    private const HEADER_ROW = 6;

    public function __construct(private Collection $groups, private array $meta)
    {
    }

    public function build(): Spreadsheet
    {
        $book = new Spreadsheet();
        $ws = $book->getActiveSheet()->setTitle('Debtors');

        $this->titleBlock($ws);
        $this->headerRow($ws);

        $row = self::HEADER_ROW + 1;

        foreach ($this->groups as $group) {
            if ($group['label'] !== null) {
                $ws->mergeCells("A{$row}:I{$row}");
                $ws->setCellValue("A{$row}", "{$group['label']}  —  {$group['totals']['count']} debtor(s)");
                $this->fill($ws, "A{$row}:I{$row}", 'DCE6F1', true);
                $row++;
            }

            $n = 0;
            foreach ($group['rows'] as $r) {
                $n++;
                $ws->setCellValue("A{$row}", $n);
                $ws->setCellValueExplicit("B{$row}", (string) $r->admission_no, DataType::TYPE_STRING);
                $ws->setCellValue("C{$row}", $r->student_name);
                $ws->setCellValue("D{$row}", $r->grade_name);
                $ws->setCellValue("E{$row}", $r->stream_name);
                $ws->setCellValue("F{$row}", (float) $r->invoiced);
                $ws->setCellValue("G{$row}", (float) $r->paid);
                $ws->setCellValue("H{$row}", (float) $r->balance);
                $ws->setCellValue("I{$row}", (float) $r->balance_pct);
                $row++;
            }

            if ($this->meta['grouped']) {
                $row = $this->totalRow($ws, $row, 'Subtotal', $group['totals'], 'F2F2F2');
            }
        }

        $dataEnd = $row - 1;
        $row = $this->totalRow($ws, $row, 'GRAND TOTAL', $this->meta['grand'], 'F8CBAD');
        $last = $row - 1;

        // Formats, borders, alignment
        $ws->getStyle("F" . (self::HEADER_ROW + 1) . ":H{$last}")->getNumberFormat()->setFormatCode('#,##0.00');
        $ws->getStyle("I" . (self::HEADER_ROW + 1) . ":I{$last}")->getNumberFormat()->setFormatCode('0.0"%"');
        $ws->getStyle("A" . self::HEADER_ROW . ":I{$last}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BFBFBF');
        $ws->getStyle("A" . (self::HEADER_ROW + 1) . ":A{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['A' => 6, 'B' => 14, 'C' => 34, 'D' => 14, 'E' => 14, 'F' => 16, 'G' => 16, 'H' => 16, 'I' => 12] as $col => $w) {
            $ws->getColumnDimension($col)->setWidth($w);
        }

        $ws->freezePane('A' . (self::HEADER_ROW + 1));
        if (! $this->meta['grouped']) {
            $ws->setAutoFilter('A' . self::HEADER_ROW . ":I{$dataEnd}");
        }

        // Print setup: A4 portrait, fit to width, repeat header on every page
        $ws->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setFitToPage(true)
            ->setRowsToRepeatAtTopByStartAndEnd(self::HEADER_ROW, self::HEADER_ROW);

        return $book;
    }

    private function titleBlock(Worksheet $ws): void
    {
        foreach ([1, 2, 3, 4] as $r) {
            $ws->mergeCells("A{$r}:I{$r}");
            $ws->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $ws->setCellValue('A1', $this->meta['school']);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(15);

        $ws->setCellValue('A2', 'DEBTORS LIST');
        $ws->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $ws->setCellValue('A3', $this->meta['filters']);
        $ws->getStyle('A3')->getFont()->setItalic(true);

        $ws->setCellValue('A4', "Generated {$this->meta['generated']}  |  Amounts in {$this->meta['currency']}");
        $ws->getStyle('A4')->getFont()->setSize(9)->getColor()->setRGB('666666');
    }

    private function headerRow(Worksheet $ws): void
    {
        $r = self::HEADER_ROW;

        foreach (['#', 'Adm No', 'Student', 'Grade', 'Stream', 'Invoiced', 'Paid', 'Balance', 'Balance %'] as $i => $label) {
            $ws->setCellValue(chr(65 + $i) . $r, $label);
        }

        $range = "A{$r}:I{$r}";
        $this->fill($ws, $range, '1F3864', true);
        $ws->getStyle($range)->getFont()->getColor()->setRGB('FFFFFF');
        $ws->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $ws->getRowDimension($r)->setRowHeight(22);
    }

    private function totalRow(Worksheet $ws, int $row, string $label, array $t, string $color): int
    {
        $ws->mergeCells("A{$row}:E{$row}");
        $ws->setCellValue("A{$row}", "{$label} ({$t['count']})");
        $ws->setCellValue("F{$row}", (float) $t['invoiced']);
        $ws->setCellValue("G{$row}", (float) $t['paid']);
        $ws->setCellValue("H{$row}", (float) $t['balance']);
        $ws->setCellValue("I{$row}", (float) $t['pct']);

        $this->fill($ws, "A{$row}:I{$row}", $color, true);
        $ws->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return $row + 1;
    }

    private function fill(Worksheet $ws, string $range, string $rgb, bool $bold = false): void
    {
        $style = $ws->getStyle($range);
        $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
        if ($bold) {
            $style->getFont()->setBold(true);
        }
    }
}
