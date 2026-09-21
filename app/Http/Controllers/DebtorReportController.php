<?php

namespace App\Http\Controllers;

use App\Exports\DebtorsExcelExport;
use App\Exports\DebtorsPdfExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\DebtorReportRequest;
use App\Services\Finance\DebtorReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DebtorReportController extends Controller
{
    public function __construct(private DebtorReportService $service)
    {
    }

    /** Finance → Reports landing page. */
    public function hub()
    {
        return view('finance.reports.index');
    }

    public function index(Request $request)
    {
        $options = $this->service->filterOptions();

        $config = [
            'routes' => [
                'data'    => route('finance.reports.debtors.data'),
                'summary' => route('finance.reports.debtors.summary'),
                'excel'   => route('finance.reports.debtors.excel'),
                'pdf'     => route('finance.reports.debtors.pdf'),
            ],
            'grades'   => $options['grades'],
            'streams'  => $options['streams'],
            'initial'  => [
                'grade_ids'  => array_map('strval', (array) $request->input('grade_ids', [])),
                'stream_ids' => array_map('strval', (array) $request->input('stream_ids', [])),
            ],
        ];

        return view('finance.reports.debtors', [
            'config'   => $config,
            'currency' => config('finance_reports.currency'),
        ]);
    }

    /** Server-side DataTables endpoint. */
    public function data(DebtorReportRequest $request): JsonResponse
    {
        $q = $this->service->query($request->filters());
        $total = (clone $q)->count();

        $dir = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $colIdx = (int) $request->input('order.0.column', 7);

        if ($colIdx === 3) {
            $q->orderByRaw("LENGTH(t.grade_sort) {$dir}, t.grade_sort {$dir}");
        } else {
            $sortable = [
                1 => 't.admission_no', 2 => 't.student_name', 4 => 't.stream_name',
                5 => 't.invoiced', 6 => 't.paid', 7 => 't.balance', 8 => 't.balance_pct',
            ];
            $q->orderBy($sortable[$colIdx] ?? 't.balance', $dir);
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = min(max((int) $request->input('length', 25), 1), 500);

        $rows = $q->orderBy('t.student_name')->select('t.*')->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $rows->values()->map(fn ($r, $i) => [
                'no'           => $start + $i + 1,
                'admission_no' => $r->admission_no,
                'student_name' => $r->student_name,
                'grade_name'   => $r->grade_name,
                'stream_name'  => $r->stream_name,
                'invoiced'     => (float) $r->invoiced,
                'paid'         => (float) $r->paid,
                'balance'      => (float) $r->balance,
                'balance_pct'  => (float) $r->balance_pct,
            ]),
        ]);
    }

    /** KPI cards + breakdown table. */
    public function summary(DebtorReportRequest $request): JsonResponse
    {
        $f = $request->filters();

        return response()->json([
            'summary'   => $this->service->summary($f),
            'breakdown' => $this->service->breakdown($f),
            'group_by'  => $f['group_by'] === 'stream' ? 'stream' : 'grade',
        ]);
    }

    public function excel(DebtorReportRequest $request)
    {
        $f = $request->filters();
        $groups = $this->service->groups($f);

        $book = (new DebtorsExcelExport($groups, $this->meta($f, $groups)))->build();

        return response()->streamDownload(
            fn () => (new Xlsx($book))->save('php://output'),
            $this->filename('xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function pdf(DebtorReportRequest $request)
    {
        @set_time_limit(120);
        @ini_set('memory_limit', '512M');

        $f = $request->filters();
        $groups = $this->service->groups($f);

        $pdf = (new DebtorsPdfExport())->render($groups, $this->meta($f, $groups));

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->filename('pdf') . '"',
        ]);
    }

    private function meta(array $f, $groups): array
    {
        return [
            'school'    => setting()['school_name'] ?? config('app.name'), // adjust the settings key if yours differs
            'filters'   => $this->service->describe($f),
            'grouped'   => $f['group_by'] !== 'none',
            'grand'     => $this->service->totals($groups->pluck('rows')->collapse()),
            'currency'  => config('finance_reports.currency'),
            'generated' => now()->format('d M Y, H:i'),
        ];
    }

    private function filename(string $ext): string
    {
        return 'debtors-list-' . now()->format('Ymd-Hi') . '.' . $ext;
    }
}
