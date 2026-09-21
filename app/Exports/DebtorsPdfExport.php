<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class DebtorsPdfExport
{
    /**
     * Rows per WriteHTML() call. mPDF fails with a PCRE backtrack-limit error
     * when one huge HTML string is written at once, so we feed it in chunks.
     */
    private const CHUNK = 200;

    public function render(Collection $groups, array $meta): string
    {
        $tmp = storage_path('app/mpdf');
        is_dir($tmp) || mkdir($tmp, 0775, true); // mPDF needs a writable tempDir (classic failure on Windows/Linux)

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 12,
            'margin_bottom' => 16,
            'tempDir'       => $tmp,
        ]);

        $mpdf->SetTitle('Debtors list');
        $mpdf->SetHTMLFooter(
            '<table width="100%" style="font-size:8pt;color:#666"><tr>'
            . '<td>Generated ' . e($meta['generated']) . '</td>'
            . '<td align="right">Page {PAGENO} of {nbpg}</td></tr></table>'
        );

        $mpdf->WriteHTML($this->css(), HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML(view('finance.reports.pdf.debtors-head', $meta)->render(), HTMLParserMode::HTML_BODY);

        if ($meta['grand']['count'] === 0) {
            $mpdf->WriteHTML('<p style="text-align:center;color:#666;margin-top:20px">No debtors match the selected filters.</p>', HTMLParserMode::HTML_BODY);

            return $mpdf->Output('', Destination::STRING_RETURN);
        }

        foreach ($groups as $g) {
            if ($g['label'] !== null) {
                $mpdf->WriteHTML(
                    '<h3 class="grp">' . e($g['label']) . ' <span>(' . $g['totals']['count'] . ' debtors)</span></h3>',
                    HTMLParserMode::HTML_BODY
                );
            }

            $chunks = $g['rows']->chunk(self::CHUNK);
            $lastIdx = $chunks->count() - 1;

            foreach ($chunks as $n => $chunk) {
                $mpdf->WriteHTML(view('finance.reports.pdf.debtors-table', [
                    'rows'       => $chunk,
                    'offset'     => $n * self::CHUNK,
                    'showHead'   => true,
                    'totals'     => ($meta['grouped'] && $n === $lastIdx) ? $g['totals'] : null,
                    'totalLabel' => 'Subtotal',
                    'totalClass' => 'sub',
                ])->render(), HTMLParserMode::HTML_BODY);
            }
        }

        $mpdf->WriteHTML(view('finance.reports.pdf.debtors-table', [
            'rows'       => collect(),
            'offset'     => 0,
            'showHead'   => false,
            'totals'     => $meta['grand'],
            'totalLabel' => 'GRAND TOTAL',
            'totalClass' => 'grand',
        ])->render(), HTMLParserMode::HTML_BODY);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function css(): string
    {
        return <<<'CSS'
        body { font-family: dejavusanscondensed, sans-serif; font-size: 9pt; color: #222; }
        table.t { width: 100%; border-collapse: collapse; }
        table.t th { background: #1F3864; color: #fff; padding: 4px; font-size: 8.5pt; }
        table.t td { padding: 3px 4px; border-bottom: 0.1mm solid #d9d9d9; }
        table.t tr.alt td { background: #f7f9fc; }
        table.t tr.sub td { background: #eeeeee; font-weight: bold; border-top: 0.3mm solid #999; }
        table.t tr.grand td { background: #f8cbad; font-weight: bold; }
        .r { text-align: right; } .c { text-align: center; }
        .hi { color: #b00020; font-weight: bold; }
        .mid { color: #c25e00; font-weight: bold; }
        h3.grp { background: #dce6f1; padding: 4px 6px; margin: 12px 0 4px; font-size: 10pt; }
        h3.grp span { font-weight: normal; color: #555; font-size: 9pt; }
        table.kpi td { border: 0.2mm solid #d9d9d9; background: #f8f9fa; text-align: center; font-size: 8.5pt; }
        CSS;
    }
}
