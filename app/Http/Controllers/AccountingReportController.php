<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\ExpenseTransaction;
use App\Models\ExpenseTransactionItem;
use App\Models\IncomeTransaction;
use App\Models\IncomeTransactionItem;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Services\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AccountingReportController extends Controller
{
    // ADJUST if your `payments` columns are named differently
    private const PAYMENT_DATE   = 'paid_on';
    private const PAYMENT_AMOUNT = 'amount';

    public function index()
    {
        $academicYear = (new Common())->resolveCurrentTerm()->academic_year ?? (string) now()->year;

        // ---- Totals + category breakdown (single source of truth) ----
        [$incomeByCategory, $expenseByCategory, $incomeTotal, $expenseTotal] = $this->buildReport($academicYear, null);

        // Recent lists: manual transactions only (fee payments / payroll are not IncomeTransaction rows)
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

        // ---- Monthly trend ----
        [$monthlyIncomeData, $monthlyExpenseData] = $this->monthlySeries((int) $academicYear);

        $monthlyLabels = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyLabels[] = Carbon::create()->month($m)->format('M');
        }

        $topIncomeCategory  = $incomeByCategory->sortByDesc('total')->first();
        $topExpenseCategory = $expenseByCategory->sortByDesc('total')->first();

        // ---- Top payer / top vendor (manual transactions only) ----
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

        // ---- Month-over-month change ----
        $currentMonth = now()->month;
        $prevMonth    = $currentMonth === 1 ? 12 : $currentMonth - 1;

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
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function summary(Request $request)
    {
        abort_unless($request->user()?->hasPermission('accounting.view'), 403);

        $academicYear = $request->input('academic_year') ?? (new Common())->resolveCurrentTerm()->academic_year ?? (string) now()->year;
        $term = $request->input('term') ? (int) $request->input('term') : null;

        [$income, $expenses, $incomeTotal, $expenseTotal] = $this->buildReport($academicYear, $term);

        return view('accounting.reports.summary', [
            'income'       => $income,
            'expenses'     => $expenses,
            'incomeTotal'  => $incomeTotal,
            'expenseTotal' => $expenseTotal,
            'net'          => $incomeTotal - $expenseTotal,
            'academicYear' => $academicYear,
            'term'         => $term,
        ]);
    }

    /**
     * Returns [incomeByCategory, expenseByCategory, incomeTotal, expenseTotal].
     *
     * INCOME   = manual income transactions + fee payments + other payments
     * EXPENSES = manual expense transactions + approved/paid staff payment schedules
     *
     * Shared by dashboard, summary screen and Excel export so they can never disagree.
     */
    private function buildReport(string $academicYear, ?int $term): array
    {
        [$start, $end] = $this->periodRange($academicYear, $term);

        // ---- Manual income ----
        $manualIncome = IncomeTransactionItem::query()
            ->join('income_categories', 'income_categories.id', '=', 'income_transaction_items.income_category_id')
            ->join('income_transactions', 'income_transactions.id', '=', 'income_transaction_items.income_transaction_id')
            ->where('income_transactions.academic_year', $academicYear)
            ->when($term, fn ($q) => $q->where('income_transactions.term', $term))
            ->groupBy('income_categories.id', 'income_categories.name')
            ->select('income_categories.name', DB::raw('SUM(income_transaction_items.amount) as total'))
            ->get();

        // ---- Manual expenses ----
        $manualExpenses = ExpenseTransactionItem::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expense_transaction_items.expense_category_id')
            ->join('expense_transactions', 'expense_transactions.id', '=', 'expense_transaction_items.expense_transaction_id')
            ->where('expense_transactions.academic_year', $academicYear)
            ->when($term, fn ($q) => $q->where('expense_transactions.term', $term))
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->select('expense_categories.name', DB::raw('SUM(expense_transaction_items.amount) as total'))
            ->get();

        $income   = $this->mergeByName($manualIncome->concat($this->paymentIncomeLines($start, $end)));
        $expenses = $this->mergeByName($manualExpenses->concat($this->payrollExpenseLines($start, $end)));

        return [$income, $expenses, $income->sum('total'), $expenses->sum('total')];
    }

    /** Academic year = calendar year; term narrows to the term's date range. */
    private function periodRange(string $academicYear, ?int $term): array
    {
        $year  = (int) $academicYear;
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();

        if ($term) {
            // ADJUST column names if your academic_terms table differs
            $t = AcademicTerm::where('academic_year', $academicYear)->where('term_number', $term)->first();
            if ($t) {
                $start = Carbon::parse($t->start_date)->startOfDay();
                $end   = Carbon::parse($t->end_date)->endOfDay();
            }
        }

        return [$start, $end];
    }

    /** Fee payments (linked to an invoice) vs other payments (no invoice). */
    private function paymentIncomeLines(Carbon $start, Carbon $end): Collection
    {
        $q = Payment::query()->whereBetween(self::PAYMENT_DATE, [$start, $end]);

        $fees  = (float) (clone $q)->whereNotNull('invoice_id')->sum(self::PAYMENT_AMOUNT);
        $other = (float) (clone $q)->whereNull('invoice_id')->sum(self::PAYMENT_AMOUNT);

        return collect([
            ['name' => 'School Fees',    'total' => $fees],
            ['name' => 'Other Payments', 'total' => $other],
        ])->filter(fn ($r) => $r['total'] > 0)->map(fn ($r) => (object) $r)->values();
    }

    /** Approved/paid payment schedules whose period falls in the range. */
    private function payrollSchedules(Carbon $start, Carbon $end): Collection
    {
        return PaymentSchedule::with('lines')
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('period', [$start->copy()->startOfMonth()->toDateString(), $end->toDateString()])
            ->get();
    }

    /** Employer cost of one schedule, split by expense category. */
    private function payrollCost(PaymentSchedule $schedule): array
    {
        $l = $schedule->lines;

        return [
            'Staff Salaries & Allowances' => round(
                $l->sum('gross_pay') + $l->sum('non_taxable_allowances') + $l->sum('one_off_reimbursement'), 2
            ),
            'Employer NSSF'               => round($l->sum('employer_nssf'), 2),
            'Employer Housing Levy (AHL)' => round($l->sum('employer_housing_levy'), 2),
            'NITA Levy'                   => round($l->sum('nita'), 2),
        ];
    }

    private function payrollExpenseLines(Carbon $start, Carbon $end): Collection
    {
        $totals = [];
        foreach ($this->payrollSchedules($start, $end) as $schedule) {
            foreach ($this->payrollCost($schedule) as $name => $amount) {
                $totals[$name] = ($totals[$name] ?? 0) + $amount;
            }
        }

        return collect($totals)
            ->filter(fn ($v) => $v > 0)
            ->map(fn ($v, $name) => (object) ['name' => $name, 'total' => $v])
            ->values();
    }

    /** Same category name from different sources collapses into one line. */
    private function mergeByName(Collection $lines): Collection
    {
        return $lines->groupBy('name')
            ->map(fn ($g, $name) => (object) ['name' => $name, 'total' => round((float) $g->sum('total'), 2)])
            ->sortBy('name')
            ->values();
    }

    /** 12-slot monthly series [income[], expense[]] for the chart. */
    private function monthlySeries(int $year): array
    {
        $manualIncome = IncomeTransaction::where('academic_year', (string) $year)
            ->selectRaw('MONTH(transaction_date) as month, SUM(total_amount) as total')
            ->groupBy('month')->pluck('total', 'month');

        $manualExpense = ExpenseTransaction::where('academic_year', (string) $year)
            ->selectRaw('MONTH(transaction_date) as month, SUM(total_amount) as total')
            ->groupBy('month')->pluck('total', 'month');

        $date = self::PAYMENT_DATE;
        $amt  = self::PAYMENT_AMOUNT;
        $payments = Payment::query()
            ->whereYear($date, $year)
            ->selectRaw("MONTH({$date}) as month, SUM({$amt}) as total")
            ->groupBy('month')->pluck('total', 'month');

        $payroll = array_fill(1, 12, 0.0);
        $start = Carbon::create($year, 1, 1);
        $end   = Carbon::create($year, 12, 31);
        foreach ($this->payrollSchedules($start, $end) as $schedule) {
            $payroll[$schedule->period->month] += array_sum($this->payrollCost($schedule));
        }

        $income = $expense = [];
        for ($m = 1; $m <= 12; $m++) {
            $income[]  = (float) ($manualIncome[$m] ?? 0) + (float) ($payments[$m] ?? 0);
            $expense[] = (float) ($manualExpense[$m] ?? 0) + $payroll[$m];
        }

        return [$income, $expense];
    }

    public function export(Request $request)
    {
        abort_unless($request->user()?->hasPermission('accounting.reports.view'), 403);

        $academicYear = $request->input('academic_year') ?? (new Common())->resolveCurrentTerm()->academic_year ?? (string) now()->year;
        $term = $request->input('term') ? (int) $request->input('term') : null;

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
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9F2D9');
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
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2D9D9');
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
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');

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
