<?php

/*
|--------------------------------------------------------------------------
| Finance → Reports: schema map (mapped to the DevKei SMS schema)
|--------------------------------------------------------------------------
| Every table/column the reports read is declared here.
|
| VERIFY before first use:
|   1. SELECT status, COUNT(*) FROM invoices GROUP BY status;
|      -> put every "not a real charge" status in invoices.excluded_statuses.
|   2. Compare one student's Balance here with their statement page.
|
| In `name_sql`, `s` is the students (users) table alias.
*/
return [

    'currency' => 'KES',

    // Students are rows in `users`; admission number = users.userID.
    // Only users that have a student_enrollments row can appear in the report.
    'students' => [
        'table'        => 'users',
        'admission_no' => 'userID',
        'name_sql'     => "CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name)",
        'status_col'   => 'status',
        'active_value' => 'active',
        'soft_deletes' => true,
    ],

    'enrollments' => [
        'table'        => 'student_enrollments',
        'student_fk'   => 'user_id',
        'grade_fk'     => 'grade_level_id',
        'stream_fk'    => 'stream_id',
        'status_col'   => 'status',
        'active_value' => 'active',
        'soft_deletes' => true,
    ],

    'grades' => [
        'table'        => 'grade_levels',
        'name'         => 'name',
        'sort'         => 'sequence', // KG1=1 … Grade 12=17
        'soft_deletes' => true,       // hides deleted grades from the filter dropdown only
    ],

    'streams' => [
        'table'        => 'streams',
        'name'         => 'name',
        'grade_fk'     => 'grade_level_id',
        'soft_deletes' => true,       // hides deleted streams from the filter dropdown only
    ],

    'invoices' => [
        'table'             => 'invoices',
        'student_fk'        => 'user_id',
        'amount'            => 'total_amount',
        'status_col'        => 'status',
        'excluded_statuses' => ['cancelled', 'void'], // adjust after running check #1
        'soft_deletes'      => true,
    ],

    // payments has no status column, and some rows have invoice_id = NULL
    // (manually reconciled bank receipts), so payments are summed by user_id.
    'payments' => [
        'table'             => 'payments',
        'student_fk'        => 'user_id',
        'amount'            => 'amount',
        'status_col'        => null,
        'included_statuses' => [],
        'soft_deletes'      => true,
    ],
];
