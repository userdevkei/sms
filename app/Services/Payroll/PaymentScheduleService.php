<?php

namespace App\Services\Payroll;

use App\Models\PaymentSchedule;
use App\Models\PaymentScheduleLine;
use App\Models\StaffPayee;
use Illuminate\Support\Facades\DB;

class PaymentScheduleService
{
    public function __construct(private KenyaPayrollCalculator $calc) {}

    /** (Re)build all lines of a DRAFT schedule from the current payee register. */
    public function generate(PaymentSchedule $schedule): void
    {
        abort_unless($schedule->isDraft(), 422, 'Only draft schedules can be regenerated.');

        DB::transaction(function () use ($schedule) {
            $schedule->lines()->forceDelete();
            $monthStart = $schedule->period->copy()->startOfMonth();
            $daysInMonth = $monthStart->daysInMonth;

            $payees = StaffPayee::active()->employedIn($monthStart)
                ->with(['items' => fn ($q) => $q->where('is_active', true)])->get();

            foreach ($payees as $p) {
                $from = $p->start_date && $p->start_date->gt($monthStart) ? $p->start_date : $monthStart;
                $to   = $p->end_date && $p->end_date->lt($monthStart->copy()->endOfMonth()) ? $p->end_date : $monthStart->copy()->endOfMonth();
                $days = min($daysInMonth, $from->diffInDays($to) + 1);
                $factor = $days / $daysInMonth;

                $sum = fn (string $kind) => (float) $p->items->where('kind', $kind)->sum('amount');

                $line = new PaymentScheduleLine([
                    'payment_schedule_id'    => $schedule->id,
                    'staff_payee_id'         => $p->id,
                    'staff_no'               => $p->staff_no,
                    'full_name'              => $p->full_name,
                    'kra_pin'                => $p->kra_pin,
                    'nssf_no'                => $p->nssf_no,
                    'shif_no'                => $p->shif_no,
                    'payment_method'         => $p->payment_method,
                    'bank_name'              => $p->bank_name,
                    'bank_account'           => $p->bank_account,
                    'mpesa_phone'            => $p->mpesa_phone,
                    'basic_salary'           => $p->basic_salary,
                    'days_worked'            => $days,
                    'days_in_month'          => $daysInMonth,
                    'taxable_allowances'     => round($sum('allowance') * $factor, 2),
                    'non_taxable_allowances' => $sum('reimbursement'),
                    'pension'                => $sum('pension'),
                    'insurance_premium'      => $sum('insurance_premium'),
                    'mortgage_interest'      => $sum('mortgage_interest'),
                    'other_deductions'       => $sum('deduction'),
                ]);
                $this->recalculate($line);
                $line->save();
            }

            $this->refreshTotals($schedule);
        });
    }

    /** Recompute one line from its stored inputs (basic is prorated by days_worked / days_in_month). */
    public function recalculate(PaymentScheduleLine $l): void
    {
        $factor = $l->days_in_month > 0 ? min(1, $l->days_worked / $l->days_in_month) : 1;

        $out = $this->calc->calculate([
            'basic'                  => round($l->basic_salary * $factor, 2),
            'taxable_allowances'     => $l->taxable_allowances + $l->one_off_allowance,
            'non_taxable_allowances' => $l->non_taxable_allowances + $l->one_off_reimbursement,
            'pension'                => $l->pension,
            'insurance_premium'      => $l->insurance_premium,
            'mortgage_interest'      => $l->mortgage_interest,
            'other_deductions'       => $l->other_deductions + $l->one_off_deduction,
        ]);

        $l->fill($out);
    }

    public function refreshTotals(PaymentSchedule $s): void
    {
        $t = $s->lines()->selectRaw('
            COALESCE(SUM(gross_pay),0) gross, COALESCE(SUM(paye),0) paye, COALESCE(SUM(nssf),0) nssf,
            COALESCE(SUM(shif),0) shif, COALESCE(SUM(housing_levy),0) ahl, COALESCE(SUM(net_pay),0) net,
            COALESCE(SUM(gross_pay + non_taxable_allowances + one_off_reimbursement + employer_nssf + employer_housing_levy + nita),0) cost
        ')->first();

        $s->update([
            'total_gross'         => $t->gross,
            'total_paye'          => $t->paye,
            'total_nssf'          => $t->nssf,
            'total_shif'          => $t->shif,
            'total_housing_levy'  => $t->ahl,
            'total_net'           => $t->net,
            'total_employer_cost' => $t->cost,
            'rate_snapshot'       => config('kenya_payroll'),
        ]);
    }
}
