<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeTransactionRequest;
use App\Models\IncomeCategory;
use App\Models\IncomeTransaction;
use App\Models\Setting;
use App\Services\Common;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class IncomeTransactionController extends Controller
{
    public function index()
    {
        $categories = IncomeCategory::where('status', 'active')->orderBy('name')->get();
        $academicYear = Setting::allSettings()['current_academic_year'] ?? (string) now()->year;
        $academicYears = IncomeTransaction::query()->distinct()->orderByDesc('academic_year')->pluck('academic_year');
        $canDelete = request()->user()?->hasPermission('income.delete') ?? false;
        $canUpdate = request()->user()?->hasPermission('income.update') ?? false;

        return view('accounting.income.index', compact('categories', 'academicYear', 'academicYears', 'canDelete', 'canUpdate'));
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));

        $query = IncomeTransaction::query()->with(['items.category', 'recordedBy']);

        if ($year = $request->input('filter_year')) $query->where('academic_year', $year);
        if ($term = $request->input('filter_term')) $query->where('term', $term);
        if ($categoryId = $request->input('filter_category')) {
            $query->whereHas('items', fn ($q) => $q->where('income_category_id', $categoryId));
        }
        if ($dateFrom = $request->input('filter_date_from')) {
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('filter_date_to')) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }

        $totalRecords = (clone $query)->count();

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(function ($q) use ($search) {
                $q->where('received_from', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        $transactions = $query->orderByDesc('transaction_date')->offset($start)->limit($length)->get();

        $data = $transactions->map(function ($t) {
            $categoryNames = $t->items->pluck('category.name')->unique();

            return [
                'id'            => $t->id,
                'date'          => $t->transaction_date->format('d M Y'),
                'reference'     => $t->reference ?: '—',
                'category'      => $categoryNames->count() > 1 ? 'Multiple' : ($categoryNames->first() ?? '—'),
                'items_count'   => $t->items->count(),
                'amount'        => number_format($t->total_amount, 2),
                'received_from' => $t->received_from ?: '—',
                'term'          => "Term {$t->term}, {$t->academic_year}",
                'recorded_by'   => trim($t->recordedBy->first_name.' '.$t->recordedBy->last_name),
                'edit_url'      => route('accounting.income.edit', $t->id),
                'delete_url'    => route('accounting.income.destroy', $t->id),
                'receipt_url'   => route('accounting.income.receipt', $t->id),
            ];
        });

        return response()->json(['draw' => $draw, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filteredRecords, 'data' => $data]);
    }

    public function create()
    {
        $categories = IncomeCategory::where('status', 'active')->orderBy('name')->get();

        return view('accounting.income.create', compact('categories'));
    }

    public function store(StoreIncomeTransactionRequest $request)
    {
        $this->persist($request->validated(), new IncomeTransaction, $request->user()->id);

        return redirect()->route('accounting.income.index')->with('success', 'Income recorded successfully.');
    }

    public function edit(IncomeTransaction $incomeTransaction)
    {
        $incomeTransaction->load('items');
        $categories = IncomeCategory::where('status', 'active')->orderBy('name')->get();

        return view('accounting.income.edit', ['transaction' => $incomeTransaction, 'categories' => $categories]);
    }

    public function update(StoreIncomeTransactionRequest $request, IncomeTransaction $incomeTransaction)
    {
        $this->persist($request->validated(), $incomeTransaction, $request->user()->id);

        return redirect()->route('accounting.income.index')->with('success', 'Income updated successfully.');
    }

    /**
     * Shared by store() and update(): recompute item amounts, replace the
     * item set, and cache the new total on the header — all inside one
     * transaction so a header/items mismatch can never be left half-saved.
     */
    private function persist(array $validated, IncomeTransaction $transaction, string $userId): void
    {
        DB::transaction(function () use ($validated, $transaction, $userId) {
            $items = collect($validated['items'])->map(fn ($item) => [
                'income_category_id' => $item['category_id'],
                'description'        => $item['description'],
                'quantity'           => $item['quantity'],
                'unit_price'         => $item['unit_price'],
                'amount'             => round($item['quantity'] * $item['unit_price'], 2),
            ]);

            $transaction->fill([
                'reference'        => $validated['reference'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'academic_year'    => $validated['academic_year'],
                'term'             => $validated['term'],
                'received_from'    => $validated['received_from'] ?? null,
                'payment_method'   => $validated['payment_method'] ?? null,
                'description'      => $validated['description'] ?? null,
                'total_amount'     => $items->sum('amount'),
                'recorded_by'      => $transaction->recorded_by ?? $userId,
            ])->save();

            $transaction->items()->delete();
            $transaction->items()->createMany($items->all());
        });
    }

    public function destroy(IncomeTransaction $incomeTransaction): JsonResponse
    {
        $incomeTransaction->delete(); // items cascade via FK

        return response()->json(['success' => true, 'message' => 'Transaction deleted successfully.']);
    }

    public function receipt(IncomeTransaction $incomeTransaction)
    {
        $incomeTransaction->load(['items.category', 'recordedBy']);


        $html = view('accounting.income.receipt', [
            'transaction' => $incomeTransaction,
            'schoolName'  => setting('school_name' ?? config('app.name')),
            'schoolMotto' => setting('motto' ?? null),
            'logoPath'    => $this->resolveImageBase64(setting('logo_path')), // absolute filesystem path, not a URL
        ])->render();

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_top' => 10,
            'margin_bottom' => 14,
            'margin_left' => 10,
            'margin_right' => 10,
            'tempDir' => storage_path('app/mpdf-tmp'),
        ]);

        $mpdf->WriteHTML($html);

        $filename = 'Receipt-' . ($incomeTransaction->reference ?: $incomeTransaction->id) . '.pdf';

        return response($mpdf->Output($filename, Destination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
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

    public function export(Request $request)
    {
        abort_unless($request->user()?->hasPermission('income.view'), 403);

        $query = IncomeTransaction::query()->with(['items.category', 'recordedBy']);

        if ($categoryId = $request->input('filter_category')) {
            $query->whereHas('items', fn ($q) => $q->where('income_category_id', $categoryId));
        }
        if ($year = $request->input('filter_year')) $query->where('academic_year', $year);
        if ($term = $request->input('filter_term')) $query->where('term', $term);
        if ($dateFrom = $request->input('filter_date_from')) $query->whereDate('transaction_date', '>=', $dateFrom);
        if ($dateTo = $request->input('filter_date_to')) $query->whereDate('transaction_date', '<=', $dateTo);

        $transactions = $query->orderBy('transaction_date')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Income');

        $headers = ['#', 'Date', 'Reference', 'Category', 'Description', 'Received From', 'Payment Method', 'Term', 'Recorded By', 'Amount'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('122744');
        $sheet->getStyle('A1:J1')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 2;
        $sn = 1;
        $grandTotal = 0;

        // One row per line item, not per transaction header — same convention as the
        // expense export, so a single receipt covering multiple income streams
        // (e.g. gown hire + venue on one deposit slip) breaks out by category.
        foreach ($transactions as $t) {
            foreach ($t->items as $item) {
                $sheet->fromArray([
                    $sn++,
                    $t->transaction_date->format('d M Y'),
                    $t->reference ?: '—',
                    $item->category->name,
                    $item->description,
                    $t->received_from ?: '—',
                    $t->payment_method ? ucfirst($t->payment_method) : '—',
                    "Term {$t->term}, {$t->academic_year}",
                    trim($t->recordedBy->first_name.' '.$t->recordedBy->last_name),
                    (float) $item->amount,
                ], null, "A{$row}");
                $grandTotal += $item->amount;
                $row++;
            }
        }

        $sheet->setCellValue("I{$row}", 'Total');
        $sheet->setCellValue("J{$row}", $grandTotal);
        $sheet->getStyle("I{$row}:J{$row}")->getFont()->setBold(true);
        $sheet->getStyle("I{$row}:J{$row}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'J') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->getStyle("J2:J{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        $filename = 'income-'.now()->format('Y-m-d_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn () => $writer->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
