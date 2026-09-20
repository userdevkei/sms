<?php

namespace App\Http\Controllers;

use App\Models\ExpenseTransaction;
use App\Models\ExpenseTransactionItem;
use App\Models\IncomeTransaction;
use App\Models\IncomeTransactionItem;
use App\Services\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AccountingReportController extends Controller
{
    public function index()
    {
        $academicYear = (new Common())->resolveCurrentTerm()->academic_year ?? (string) now()->year;

        $incomeTotal = IncomeTransaction::where('academic_year', $academicYear)->sum('total_amount');
        $expenseTotal = ExpenseTransaction::where('academic_year', $academicYear)->sum('total_amount');

        $recentIncome = IncomeTransaction::with('items')
            ->where('academic_year', $academicYear)
            ->latest('transaction_date')
            ->take(6)
            ->get();

        $recentExpenses = ExpenseTransaction::with('items')
            ->where('academic_year', $academicYear)
            ->latest('transaction_date')
            ->take(6)
            ->get();

        // ---- Monthly trend, for the chart ----
        $monthlyIncomeRaw = IncomeTransaction::where('academic_year', $academicYear)
            ->selectRaw('MONTH(transaction_date) as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthlyExpenseRaw = ExpenseTransaction::where('academic_year', $academicYear)
            ->selectRaw('MONTH(transaction_date) as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthlyLabels = [];
        $monthlyIncomeData = [];
        $monthlyExpenseData = [];

        for ($m = 1; $m <= 12; $m++) {
            $monthlyLabels[]     = Carbon::create()->month($m)->format('M');
            $monthlyIncomeData[]  = (float) ($monthlyIncomeRaw[$m] ?? 0);
            $monthlyExpenseData[] = (float) ($monthlyExpenseRaw[$m] ?? 0);
        }

        // ---- Category breakdown (reuses the same builder as the export, so numbers can never drift) ----
        [$incomeByCategory, $expenseByCategory] = $this->buildReport($academicYear, null);

        $topIncomeCategory = $incomeByCategory->sortByDesc('total')->first();
        $topExpenseCategory = $expenseByCategory->sortByDesc('total')->first();

        // ---- Top payer / top vendor ----
        $topIncomeSource = IncomeTransaction::where('academic_year', $academicYear)
            ->whereNotNull('received_from')
            ->select('received_from', DB::raw('SUM(total_amount) as total'))
            ->groupBy('received_from')
            ->orderByDesc('total')
            ->first();

        $topVendor = ExpenseTransaction::where('academic_year', $academicYear)
            ->whereNotNull('vendor')
            ->select('vendor', DB::raw('SUM(total_amount) as total'))
            ->groupBy('vendor')
            ->orderByDesc('total')
            ->first();

        // ---- Month-over-month change, for the stat card badges ----
        $currentMonth = now()->month;
        $prevMonth = $currentMonth === 1 ? 12 : $currentMonth - 1;

        $incomeChange = $this->percentChange(
            $monthlyIncomeData[$prevMonth - 1] ?? 0,
            $monthlyIncomeData[$currentMonth - 1] ?? 0
        );

        $expenseChange = $this->percentChange(
            $monthlyExpenseData[$prevMonth - 1] ?? 0,
            $monthlyExpenseData[$currentMonth - 1] ?? 0
        );

        return view('accounting.index', compact(
            'academicYear', 'incomeTotal', 'expenseTotal', 'recentIncome', 'recentExpenses',
            'monthlyLabels', 'monthlyIncomeData', 'monthlyExpenseData',
            'incomeByCategory', 'expenseByCategory',
            'topIncomeCategory', 'topExpenseCategory', 'topIncomeSource', 'topVendor',
            'incomeChange', 'expenseChange'
        ));
    }

    private function percentChange(float $previous, float $current): ?float
    {
        if ($previous <= 0) {
            return null; // nothing sensible to compare against
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function summary(Request $request)
    {
        abort_unless($request->user()?->hasPermission('accounting.view'), 403);

        $academicYear = $request->input('academic_year') ?? (new Common())->resolveCurrentTerm()->academic_year ?? (string) now()->year;
        $term = $request->input('term'); // null = whole year

        [$income, $expenses, $incomeTotal, $expenseTotal] = $this->buildReport($academicYear, $term);

        return view('accounting.reports.summary', [
            'income' => $income,
            'expenses' => $expenses,
            'incomeTotal' => $incomeTotal,
            'expenseTotal' => $expenseTotal,
            'net' => $incomeTotal - $expenseTotal,
            'academicYear' => $academicYear,
            'term' => $term,
        ]);
    }

    /**
     * Returns [incomeByCategory, expenseByCategory, incomeTotal, expenseTotal]
     * for the given period. Shared by the screen view and the Excel export
     * so the two can never disagree.
     */
    private function buildReport(string $academicYear, ?int $term): array
    {
        $incomeQuery = IncomeTransactionItem::query()
            ->join('income_categories', 'income_categories.id', '=', 'income_transaction_items.income_category_id')
            ->join('income_transactions', 'income_transactions.id', '=', 'income_transaction_items.income_transaction_id')
            ->where('income_transactions.academic_year', $academicYear)
            ->when($term, fn ($q) => $q->where('income_transactions.term', $term))
            ->groupBy('income_categories.id', 'income_categories.name')
            ->orderBy('income_categories.name')
            ->select('income_categories.name', DB::raw('SUM(income_transaction_items.amount) as total'));

        $expenseQuery = ExpenseTransactionItem::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expense_transaction_items.expense_category_id')
            ->join('expense_transactions', 'expense_transactions.id', '=', 'expense_transaction_items.expense_transaction_id')
            ->where('expense_transactions.academic_year', $academicYear)
            ->when($term, fn ($q) => $q->where('expense_transactions.term', $term))
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderBy('expense_categories.name')
            ->select('expense_categories.name', DB::raw('SUM(expense_transaction_items.amount) as total'));

        $income = $incomeQuery->get();
        $expenses = $expenseQuery->get();

        return [$income, $expenses, $income->sum('total'), $expenses->sum('total')];
    }

    public function export(Request $request)
    {
        abort_unless($request->user()?->hasPermission('accounting.reports.view'), 403);

        $academicYear = $request->input('academic_year') ?? (new Common())->resolveCurrentTerm()->academic_year ?? (string) now()->year;
        $term = $request->input('term');

        [$income, $expenses, $incomeTotal, $expenseTotal] = $this->buildReport($academicYear, $term);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Income & Expenditure');

        $period = $term ? "Term {$term}, {$academicYear}" : "Full Year {$academicYear}";
        $sheet->setCellValue('A1', 'Income & Expenditure Statement');
        $sheet->setCellValue('A2', $period);
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 4;
        $sheet->setCellValue("A{$row}", 'INCOME');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:B{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9F2D9'); // light green
        $row++;

        foreach ($income as $line) {
            $sheet->setCellValue("A{$row}", $line->name);
            $sheet->setCellValue("B{$row}", (float) $line->total);
            $row++;
        }
        $sheet->setCellValue("A{$row}", 'Total Income');
        $sheet->setCellValue("B{$row}", (float) $incomeTotal);
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'EXPENDITURE');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:B{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2D9D9'); // light red
        $row++;

        foreach ($expenses as $line) {
            $sheet->setCellValue("A{$row}", $line->name);
            $sheet->setCellValue("B{$row}", (float) $line->total);
            $row++;
        }
        $sheet->setCellValue("A{$row}", 'Total Expenditure');
        $sheet->setCellValue("B{$row}", (float) $expenseTotal);
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'NET SURPLUS / (DEFICIT)');
        $sheet->setCellValue("B{$row}", (float) ($incomeTotal - $expenseTotal));
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:B{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC'); // amber

        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getStyle("B5:B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        $filename = "income-expenditure-{$academicYear}".($term ? "-t{$term}" : '').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn () => $writer->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
