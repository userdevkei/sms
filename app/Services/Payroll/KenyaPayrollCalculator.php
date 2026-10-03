<?php

namespace App\Services\Payroll;

/**
 * Monthly Kenyan payroll maths. Pure function: array in, array out. All rates come from config/kenya_payroll.php.
 *
 * Order: gross -> NSSF, SHIF, AHL (all deductible) -> taxable pay -> PAYE (bands - reliefs) -> net.
 */
class KenyaPayrollCalculator
{
    public function calculate(array $in): array
    {
        $c = config('kenya_payroll');

        $gross      = $this->r(($in['basic'] ?? 0) + ($in['taxable_allowances'] ?? 0));
        $nonTaxable = $this->r($in['non_taxable_allowances'] ?? 0);
        $pension    = $this->r($in['pension'] ?? 0);
        $premium    = $this->r($in['insurance_premium'] ?? 0);
        $mortgage   = $this->r($in['mortgage_interest'] ?? 0);
        $other      = $this->r($in['other_deductions'] ?? 0);

        // NSSF (Tier I up to LEL, Tier II up to UEL), employer matches
        $pensionable = min($gross, $c['nssf']['upper_earnings_limit']);
        $tier1 = min($pensionable, $c['nssf']['lower_earnings_limit']) * $c['nssf']['rate'];
        $tier2 = max(0, $pensionable - $c['nssf']['lower_earnings_limit']) * $c['nssf']['rate'];
        $nssf  = $this->r($tier1 + $tier2);

        // SHIF 2.75% of gross, minimum 300, no cap
        $shif = $gross > 0 ? max((float) $c['shif']['minimum'], $this->r($gross * $c['shif']['rate'])) : 0.0;

        // Affordable Housing Levy 1.5% of gross, no cap
        $ahl = $this->r($gross * $c['housing_levy']['employee_rate']);

        // Other allowable deductions (capped)
        $pensionDeductible  = min($pension, $c['pension_deduction_cap']);
        $mortgageDeductible = min($mortgage, $c['mortgage_interest_cap']);

        $taxable = max(0, $this->r($gross - $nssf - $shif - $ahl - $pensionDeductible - $mortgageDeductible));

        $tax = $this->bandTax($taxable, $c['paye_bands']);
        $insuranceRelief = min($c['insurance_relief_cap'], $this->r($premium * $c['insurance_relief_rate']));
        $paye = $gross > 0 ? max(0, $this->r($tax - $c['personal_relief'] - $insuranceRelief)) : 0.0;

        $deductions = $nssf + $shif + $ahl + $paye + $pension + $other;
        $totalPay   = $gross + $nonTaxable;
        $net        = $this->r($totalPay - $deductions);

        return [
            'gross_pay'             => $gross,
            'nssf'                  => $nssf,
            'shif'                  => $shif,
            'housing_levy'          => $ahl,
            'taxable_pay'           => $taxable,
            'paye'                  => $paye,
            'net_pay'               => $net,
            'employer_nssf'         => $nssf,
            'employer_housing_levy' => $this->r($gross * $c['housing_levy']['employer_rate']),
            'nita'                  => $gross > 0 ? (float) $c['nita_per_employee'] : 0.0,
            'breaches_one_third'    => $totalPay > 0 && $net < $this->r($totalPay * $c['min_net_fraction']),
        ];
    }

    private function bandTax(float $taxable, array $bands): float
    {
        $tax = 0.0;
        $prev = 0.0;
        foreach ($bands as $b) {
            $limit = $b['upto'] ?? INF;
            if ($taxable <= $prev) {
                break;
            }
            $tax += (min($taxable, $limit) - $prev) * $b['rate'];
            $prev = $limit;
        }

        return $this->r($tax);
    }

    private function r($v): float
    {
        return round((float) $v, 2);
    }
}
