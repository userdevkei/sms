<?php


namespace App\Services\Payroll;

use App\Models\PaymentSchedule;
use App\Models\PaymentScheduleLine;
use App\Models\StaffPayee;
use App\Support\AmountInWords;
use App\Support\PayrollBranding;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollDocumentService
{
    private const FINAL = ['approved', 'paid'];

    public function __construct(private KenyaPayrollCalculator $calc)
    {
    }

    /** Use the rates frozen on the schedule so old documents never change when config does. */
    private function cfg(?array $snapshot): array
    {
        return $snapshot ?: config('kenya_payroll');
    }

    private function mask(?string $v): string
    {
        $v = (string)$v;

        return $v === '' ? '—' : str_repeat('*', max(0, strlen($v) - 4)) . substr($v, -4);
    }

    /* ------------------------------------------------------------------ PAYSLIP */

    public function payslip(PaymentSchedule $schedule, PaymentScheduleLine $line): array
    {
        $line->loadMissing('items');
        $cfg = $this->cfg($schedule->rate_snapshot);
        $payee = $line->staff_payee_id ? StaffPayee::withTrashed()->find($line->staff_payee_id) : null;
        $items = fn(string $k) => $line->items->where('kind', $k);

        $f = $line->days_in_month > 0 ? min(1, $line->days_worked / $line->days_in_month) : 1;

        $earnings = [['Basic salary' . ($f < 1 ? " ({$line->days_worked}/{$line->days_in_month} days)" : ''), round($line->basic_salary * $f, 2)]];
        if ($line->taxable_allowances > 0) $earnings[] = ['Taxable allowances', (float)$line->taxable_allowances];
        foreach ($items('allowance') as $i) $earnings[] = [$i->name, (float)$i->amount];

        $nonTaxable = [];
        if ($line->non_taxable_allowances > 0) $nonTaxable[] = ['Non-taxable allowances', (float)$line->non_taxable_allowances];
        foreach ($items('reimbursement') as $i) $nonTaxable[] = [$i->name . ' (reimbursement)', (float)$i->amount];

        $deductions = [
            ['PAYE (income tax)', (float)$line->paye],
            ['NSSF (employee)', (float)$line->nssf],
            ['SHIF (Social Health Insurance)', (float)$line->shif],
            ['Affordable Housing Levy', (float)$line->housing_levy],
        ];
        if ($line->pension > 0) $deductions[] = ['Pension contribution', (float)$line->pension];
        if ($line->other_deductions > 0) $deductions[] = ['Other recurring deductions', (float)$line->other_deductions];
        foreach ($items('deduction') as $i) $deductions[] = [$i->name, (float)$i->amount];

        $gross = (float)$line->gross_pay;
        $totalEarning = $gross + array_sum(array_column($nonTaxable, 1));
        $totalDed = array_sum(array_column($deductions, 1));

        // PAYE working
        $taxable = (float)$line->taxable_pay;
        $pensionDed = min((float)$line->pension, $cfg['pension_deduction_cap']);
        $mortDed = min((float)$line->mortgage_interest, $cfg['mortgage_interest_cap']);
        $taxCharged = $this->calc->taxOn($taxable, $cfg['paye_bands']);
        $personal = $gross > 0 ? (float)$cfg['personal_relief'] : 0.0;
        $insRelief = min($cfg['insurance_relief_cap'], round($line->insurance_premium * $cfg['insurance_relief_rate'], 2));

        // Year to date (approved/paid schedules, same year, up to and including this period)
        $ytd = DB::table('payment_schedule_lines as l')
            ->join('payment_schedules as s', 's.id', '=', 'l.payment_schedule_id')
            ->whereNull('l.deleted_at')->whereNull('s.deleted_at')
            ->whereIn('s.status', self::FINAL)
            ->whereYear('s.period', $schedule->period->year)
            ->where('s.period', '<=', $schedule->period->toDateString())
            ->when($line->staff_payee_id, fn($q) => $q->where('l.staff_payee_id', $line->staff_payee_id), fn($q) => $q->where('l.id', $line->id))
            ->selectRaw('COALESCE(SUM(l.gross_pay),0) gross, COALESCE(SUM(l.nssf),0) nssf, COALESCE(SUM(l.shif),0) shif,
                         COALESCE(SUM(l.housing_levy),0) ahl, COALESCE(SUM(l.paye),0) paye, COALESCE(SUM(l.net_pay),0) net')
            ->first();

        $pay = match ($line->payment_method) {
            'bank' => trim(($line->bank_name ?: 'Bank') . ' · A/C ' . $this->mask($line->bank_account)),
            'mpesa' => 'M-Pesa · ' . $this->mask($line->mpesa_phone),
            default => 'Cash',
        };

        return [
            'brand' => PayrollBranding::get(),
            'schedule' => $schedule,
            'period_label' => $schedule->period->format('F Y'),
            'period_range' => $schedule->period->copy()->startOfMonth()->format('d M') . ' – ' . $schedule->period->copy()->endOfMonth()->format('d M Y'),
            'ref' => 'PS-' . $schedule->period->format('Ym') . '-' . ($line->staff_no ?: strtoupper(substr($line->id, -4))),
            'status' => ucfirst($schedule->status) . ($schedule->status === 'paid' && $schedule->paid_on ? ' · ' . $schedule->paid_on->format('d M Y') : ''),
            'name' => $line->full_name,
            'staff_no' => $line->staff_no,
            'designation' => $payee?->designation,
            'department' => $payee?->department,
            'id_number' => $payee?->id_number,
            'emp_type' => $payee ? ucfirst($payee->employment_type) : null,
            'kra_pin' => $line->kra_pin,
            'nssf_no' => $line->nssf_no,
            'shif_no' => $line->shif_no,
            'days' => $line->days_worked . ' / ' . $line->days_in_month,
            'pay_method' => $pay,
            'earnings' => $earnings,
            'non_taxable' => $nonTaxable,
            'deductions' => $deductions,
            'gross' => $gross,
            'total_earnings' => $totalEarning,
            'total_deductions' => $totalDed,
            'net' => (float)$line->net_pay,
            'net_words' => AmountInWords::kes((float)$line->net_pay),
            'tax' => [
                'gross' => $gross, 'nssf' => (float)$line->nssf, 'shif' => (float)$line->shif, 'ahl' => (float)$line->housing_levy,
                'pension' => $pensionDed, 'mortgage' => $mortDed, 'taxable' => $taxable, 'charged' => $taxCharged,
                'personal' => $personal, 'insurance' => (float)$insRelief, 'paye' => (float)$line->paye,
            ],
            'employer' => ['nssf' => (float)$line->employer_nssf, 'ahl' => (float)$line->employer_housing_levy, 'nita' => (float)$line->nita],
            'ytd' => $ytd,
            'ytd_label' => 'Jan – ' . $schedule->period->format('M Y'),
        ];
    }

    /* ----------------------------------------------------------------------- P9 */

    public function years(): array
    {
        return DB::table('payment_schedules')->whereNull('deleted_at')->whereIn('status', self::FINAL)
            ->selectRaw('DISTINCT YEAR(period) y')->pluck('y')->push(now()->year)
            ->map(fn($y) => (int)$y)->unique()->sortDesc()->values()->all();
    }

    private function yearLines(int $year)
    {
        return DB::table('payment_schedule_lines as l')
            ->join('payment_schedules as s', 's.id', '=', 'l.payment_schedule_id')
            ->whereNull('l.deleted_at')->whereNull('s.deleted_at')
            ->whereIn('s.status', self::FINAL)
            ->whereYear('s.period', $year);
    }

    public function p9Summary(int $year)
    {
        return $this->yearLines($year)
            ->whereNotNull('l.staff_payee_id')
            ->groupBy('l.staff_payee_id')
            ->selectRaw('l.staff_payee_id id, MAX(l.full_name) full_name, MAX(l.staff_no) staff_no, MAX(l.kra_pin) kra_pin,
                         COUNT(DISTINCT s.period) months, SUM(l.gross_pay) gross, SUM(l.paye) paye')
            ->orderBy('full_name')->get();
    }

    public function p9PayeeIds(int $year)
    {
        return $this->p9Summary($year)->pluck('id');
    }

    public function p9(StaffPayee $payee, int $year): array
    {
        $rows = $this->yearLines($year)->where('l.staff_payee_id', $payee->id)
            ->select('l.*', 's.period', 's.rate_snapshot')->orderBy('s.period')->get();

        $months = array_fill(1, 12, null);

        foreach ($rows as $r) {
            $cfg = $this->cfg($r->rate_snapshot ? json_decode($r->rate_snapshot, true) : null);
            $m = (int)Carbon::parse($r->period)->format('n');
            $gross = (float)$r->gross_pay;
            $e = (float)$r->nssf + min((float)$r->pension, $cfg['pension_deduction_cap']);
            $f = (float)$r->housing_levy;
            $g = (float)$r->shif;
            $h = min((float)$r->mortgage_interest, $cfg['mortgage_interest_cap']);

            $row = [
                'a' => $gross, 'b' => 0.0, 'c' => 0.0, 'd' => $gross,
                'e' => $e, 'f' => $f, 'g' => $g, 'h' => $h, 'i' => $e + $f + $g + $h,
                'j' => (float)$r->taxable_pay,
                'k' => $this->calc->taxOn((float)$r->taxable_pay, $cfg['paye_bands']),
                'l' => $gross > 0 ? (float)$cfg['personal_relief'] : 0.0,
                'm' => (float)min($cfg['insurance_relief_cap'], round($r->insurance_premium * $cfg['insurance_relief_rate'], 2)),
                'n' => (float)$r->paye,
            ];
            if ($months[$m]) {
                foreach ($row as $k => $v) {
                    $months[$m][$k] += $v;
                }
            } else {
                $months[$m] = $row;
            }
        }

        $present = array_filter($months);
        $totals = [];
        foreach (['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n'] as $c) {
            $totals[$c] = array_sum(array_column($present, $c));
        }

        return [
            'brand' => PayrollBranding::get(),
            'year' => $year,
            'payee' => $payee,
            'months' => $months,
            'totals' => $totals,
            'hasData' => count($present) > 0,
        ];
    }
}
