<?php

/*
 * Kenya statutory payroll parameters. Verify against KRA / NSSF / SHA before each financial year.
 * Last checked: Oct 2026 — PAYE bands 10–35%, personal relief 2,400, NSSF Year 4 (Feb 2026),
 * SHIF 2.75% (min 300), Affordable Housing Levy 1.5%.
 */
return [
    // Monthly PAYE bands. 'upto' = cumulative upper limit of the band (null = no limit)
    'paye_bands' => [
        ['upto' => 24000,  'rate' => 0.10],
        ['upto' => 32333,  'rate' => 0.25],
        ['upto' => 500000, 'rate' => 0.30],
        ['upto' => 800000, 'rate' => 0.325],
        ['upto' => null,   'rate' => 0.35],
    ],
    'personal_relief'       => 2400,
    'insurance_relief_rate' => 0.15,    // 15% of premiums
    'insurance_relief_cap'  => 5000,    // per month
    'pension_deduction_cap' => 30000,   // own contribution to registered scheme, per month
    'mortgage_interest_cap' => 30000,   // owner-occupied mortgage interest, per month

    'nssf' => [
        'rate'                 => 0.06,
        'lower_earnings_limit' => 9000,    // Tier I ceiling
        'upper_earnings_limit' => 108000,  // Tier II ceiling
    ],
    'shif'              => ['rate' => 0.0275, 'minimum' => 300],
    'housing_levy'      => ['employee_rate' => 0.015, 'employer_rate' => 0.015],
    'nita_per_employee' => 50,             // employer, per employee per month

    // Employment Act s.19: total deductions must not exceed 2/3 of wages (net >= 1/3)
    'min_net_fraction' => 1 / 3,

    // Statutory remittance deadline: day of the following month
    'remittance_day' => 9,

    'require_separate_approver' => false,
];
