<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseTransactionRequest;
use App\Models\ExpenseCategory;
use App\Models\ExpenseTransaction;
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

class ExpenseTransactionController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::where('status', 'active')->orderBy('name')->get();
        $academicYear = Setting::allSettings()['current_academic_year'] ?? (string) now()->year;
        $academicYears = ExpenseTransaction::query()->distinct()->orderByDesc('academic_year')->pluck('academic_year');
        $canDelete = request()->user()?->hasPermission('expenses.manage.delete') ?? false;

        return view('accounting.expense.index', compact('categories', 'academicYear', 'canDelete', 'academicYears'));
    }

    public function data(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));

        $query = ExpenseTransaction::query()->with(['items.category', 'recordedBy']);

        if ($year = $request->input('filter_year')) $query->where('academic_year', $year);
        if ($term = $request->input('filter_term')) $query->where('term', $term);
        if ($categoryId = $request->input('filter_category')) {
            $query->whereHas('items', fn ($q) => $q->where('expense_category_id', $categoryId));
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
                $q->where('vendor', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        $transactions = $query->orderByDesc('transaction_date')->offset($start)->limit($length)->get();

        $data = $transactions->map(function ($t) {
            $categoryNames = $t->items->pluck('category.name')->unique();

            return [
                'date'          => $t->transaction_date->format('d M Y'),
                'reference'     => $t->reference ?: '—',
                'category'      => $categoryNames->count() > 1 ? 'Multiple' : ($categoryNames->first() ?? '—'),
                'items_count'   => $t->items->count(),
                'amount'        => number_format($t->total_amount, 2),
                'vendor'        => $t->vendor ?: '—',
                'term'          => "Term {$t->term}, {$t->academic_year}",
                'recorded_by'   => trim($t->recordedBy->first_name.' '.$t->recordedBy->last_name),
                'edit_url'      => route('accounting.expense.edit', $t->id),
                'delete_url'      => route('accounting.expense.destroy', $t->id),
                'receipt_url' => route('accounting.expense.receipt', $t->id),
            ];
        });

        return response()->json(['draw' => $draw, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filteredRecords, 'data' => $data]);
    }

    public function create()
    {
        $categories = ExpenseCategory::where('status', 'active')->orderBy('name')->get();

        return view('accounting.expense.create', compact('categories'));
    }

    public function store(StoreExpenseTransactionRequest $request)
    {
        $this->persist($request->validated(), new ExpenseTransaction, $request->user()->id);

        return redirect()->route('accounting.expense.index')->with('success', 'Income recorded successfully.');
    }

    public function edit(ExpenseTransaction $expenseTransaction)
    {
        $expenseTransaction->load('items');
        $categories = ExpenseCategory::where('status', 'active')->orderBy('name')->get();

        return view('accounting.expense.edit', ['transaction' => $expenseTransaction, 'categories' => $categories]);
    }

    public function update(StoreExpenseTransactionRequest $request, ExpenseTransaction $expenseTransaction)
    {
        $this->persist($request->validated(), $expenseTransaction, $request->user()->id);

        return redirect()->route('accounting.expense.index')->with('success', 'Income updated successfully.');
    }
    private function persist(array $validated, ExpenseTransaction $expenseTransaction, string $userId): void
    {

        DB::transaction(function () use ($validated, $expenseTransaction, $userId) {
            $items = collect($validated['items'])->map(fn ($item) => [
                'expense_category_id' => $item['category_id'],
                'description'        => $item['description'],
                'quantity'           => $item['quantity'],
                'unit_price'         => $item['unit_price'],
                'amount'             => round($item['quantity'] * $item['unit_price'], 2),
            ]);

            $expenseTransaction->fill([
                'reference'        => $validated['reference'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'academic_year'    => $validated['academic_year'],
                'term'             => $validated['term'],
                'vendor'           => $validated['vendor'] ?? null,
                'payment_method'   => $validated['payment_method'] ?? null,
                'description'      => $validated['description'] ?? null,
                'total_amount'     => $items->sum('amount'),
                'recorded_by'      => $transaction->recorded_by ?? $userId,
            ])->save();

            $expenseTransaction->items()->delete();
            $expenseTransaction->items()->createMany($items->all());
        });
    }

    public function destroy(ExpenseTransaction $expenseTransaction): JsonResponse
    {
        $deleted = $expenseTransaction->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Delete was prevented.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Transaction deleted successfully.']);
    }

    public function receipt(ExpenseTransaction $expenseTransaction)
    {
        $expenseTransaction->load(['items.category', 'recordedBy']);


        $html = view('accounting.expense.receipt', [
            'transaction' => $expenseTransaction,
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

        $filename = 'Receipt-' . ($expenseTransaction->reference ?: $expenseTransaction->id) . '.pdf';

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
        abort_unless($request->user()?->hasPermission('expenses.view'), 403);

        $query = ExpenseTransaction::query()->with(['items.category', 'recordedBy']);

        if ($categoryId = $request->input('filter_category')) {
            $query->whereHas('items', fn ($q) => $q->where('expense_category_id', $categoryId));
        }
        if ($year = $request->input('filter_year')) $query->where('academic_year', $year);
        if ($term = $request->input('filter_term')) $query->where('term', $term);
        if ($dateFrom = $request->input('filter_date_from')) $query->whereDate('transaction_date', '>=', $dateFrom);
        if ($dateTo = $request->input('filter_date_to')) $query->whereDate('transaction_date', '<=', $dateTo);

        $transactions = $query->orderBy('transaction_date')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Expenses');

        $headers = ['#', 'Date', 'Reference', 'Category', 'Description', 'Vendor', 'Payment Method', 'Term', 'Recorded By', 'Amount'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('122744');
        $sheet->getStyle('A1:J1')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 2;
        $sn = 1;
        $grandTotal = 0;

        // One row per line item, not per transaction header — an LPO with four
        // items shows four category-attributable rows instead of collapsing to "Multiple".
        foreach ($transactions as $t) {
            foreach ($t->items as $item) {
                $sheet->fromArray([
                    $sn++,
                    $t->transaction_date->format('d M Y'),
                    $t->reference ?: '—',
                    $item->category->name,
                    $item->description,
                    $t->vendor ?: '—',
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

        $filename = 'expenses-'.now()->format('Y-m-d_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn () => $writer->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
