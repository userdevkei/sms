<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'branding' => [
                'branding.view'   => 'View school branding & setup',
                'branding.manage' => 'Manage school branding, calendar & campuses',
            ],
            'curriculum' => [
                'curriculum.view'   => 'View curriculum/levels structure',
                'curriculum.manage' => 'Manage curriculum levels & subjects',
            ],
            'admissions' => [
                'admissions.view'   => 'View applications',
                'admissions.create' => 'Create/submit applications',
                'admissions.review' => 'Review applications',
                'admissions.approve'=> 'Approve/decline applications',
            ],
            'timetable' => [
                'timetable.view'   => 'View timetable',
                'timetable.manage' => 'Manage timetable',
                'timetable.generate' => 'Generate timetable',
                'timetable.add' => 'Add timetable',
                'timetable.edit' => 'Edit timetable',
                'timetable.delete' => 'Delete timetable',
                'my-timetable.view'   => ['View my timetable', 'is_principle' => true],
                'my-timetable.class'   => ['Class timetable', 'is_principle' => true],
            ],
            'time_slots' => [
                'time_slots.view'   => 'View time slots',
                'time_slots.manage' => 'Manage time slots',
                'time_slots.add'   => 'Add time slots',
                'time_slots.remove' => 'Remove time slots',
                'time_slots.edit'   => 'Edit time slots',
            ],
            'finance' => [
                'finance.view'   => 'View finance Menu',
            ],
            'students' => [
                'students.view'   => 'View student records',
                'students.create' => 'Add students (manual)',
                'students.update' => 'Edit student records',
                'students.import' => 'Bulk import students via Excel',
                'students.delete' => 'Archive/delete student records',
                'students.profile' => 'View student profile',
                'students.manage' => 'Add, edit, import, and delete students',
                'students.statements' => 'View student statement',
                'students.impersonate' => 'Impersonate students',
            ],
            'fee_structures' => [
                'fee_structures.view'    => 'View fee structures',
                'fee_structures.create'  => 'Create fee structures (draft)',
                'fee_structures.update'  => 'Edit fee structures (draft)',
                'fee_structures.approve' => 'Approve/publish fee structure versions',
            ],
            'vote_heads' => [
                'voteheads.view'   => 'View school vote heads',
                'voteheads.manage' => 'Manage school vote heads',
                'voteheads.create'   => 'Add school vote heads',
                'voteheads.update'   => 'Edit school vote heads',
                'voteheads.delete'   => 'Delete school vote heads',
            ],
            'invoices' => [
                'invoices.view'   => 'View invoices',
                'invoices.create' => 'Generate term/supplementary invoices',
                'invoices.update' => 'Adjust invoices',
                'my-statement.view'   => ['View my fees statements', 'is_principle' => true]
            ],
            'payments' => [
                'payments.view'      => 'View payments',
                'payments.record'    => 'Record cash/manual payments',
                'payments.reconcile' => 'Reconcile M-Pesa/bank transactions',
                'my-payments.view'   => ['View my fees payments', 'is_principle' => true]
            ],
            'exemptions' => [
                'exemptions.view'    => 'View exemptions/scholarships',
                'exemptions.apply'   => 'Apply/recommend exemptions',
                'exemptions.approve' => 'Approve exemptions',
                'exemptions.manage' => 'Manage exemptions',
            ],
            'other_charges' => [
                'other_charges.view'   => 'View other charges',
                'other_charges_type.view'   => 'View other charges type',
                'other_charges.manage' => 'Create/edit other charges',
                'other_charges_type.manage' => 'Create/edit other charges type',
                'other_charges.create'   => 'Add other charges',
                'other_charges_type.create'   => 'Add other charges type',
                'other_charges_type.update'   => 'Edit other charges type',
                'other_charges_type.delete'   => 'Delete other charges type',
            ],
            'results' => [
                'results.view'        => 'View results',
                'results.enter_marks' => 'Enter marks/CATs (own subjects)',
                'results.approve'     => 'Approve results (own class/school)',
                'results.publish'     => 'Publish results to parents/students',
                'my-results.view'     => ['View my exam results', 'is_principle' => true],
                'results.report_cards.view' => ['Allows user to view report cards'],
                'grading.manage' => 'Manage grading profiles',
            ],
            'progression' => [
                'progression.view'     => 'View progression records',
                'progression.initiate'=> 'Initiate/recommend promotion',
                'progression.approve'  => 'Approve bulk promotion',
            ],
            'reports' => [
                'reports.admissions' => 'View admissions reports',
                'reports.finance'    => 'View finance reports',
                'reports.academic'   => 'View academic/results reports',
                'reports.enrollment' => 'View enrollment/progression reports',
            ],
            'transport' => [
                'transport.view'   => 'View transport module',
                'transport.manage' => 'Manage fleet, routes & stops',
                'transport.update.student-transport' => 'Update student transport routes',
            ],
            'accommodation' => [
                'accommodation.view'   => 'View accommodation module',
                'accommodation.manage' => 'Manage dorms, rooms & allocations',
            ],
            'hr' => [
                'hr.view'   => 'View HR module',
                'hr.manage' => 'Manage staff records, leave & payroll',
            ],
            'accounting' => [
                'accounting.view'   => 'View accounting module',
                'accounting.manage' => 'Manage ledger, budgets & reconciliation',
                'income.view' => 'View income module',
                'expenses.view' => 'View expenses module',
                'income.manage' => 'Manage income module',
                'expenses.manage' => 'Manage expenses module',
                'income.create' => 'Create income',
                'income.update' => 'Update income',
                'income.delete' => 'Delete income',
                'expenses.create' => 'Create expense',
                'expenses.update' => 'Update expense',
                'expenses.delete' => 'Delete expense',
                'expense.categories.create' => 'Create category',
                'expense.categories.update' => 'Update category',
                'expense.categories.delete' => 'Delete category',
                'expense.categories.manage' => 'Manage category',
                'income.categories.create' => 'Create income category',
                'income.categories.update' => 'Update income category',
                'income.categories.delete' => 'Delete income category',
                'income.categories.manage' => 'Manage income category',
            ],
            'system' => [
                'users.view'      => 'View system users',
                'users.manage'    => 'Create/edit/deactivate users',
                'roles.manage'    => 'Manage roles & permissions',
                'settings.manage' => 'Manage system settings',
                'admin.view'      => 'View system admin functions',
            ],

            'bank_reconciliation' => [
                'bank_reconciliation.view'   => 'View bank reconciliation',
                'bank_reconciliation.manage' => 'Manage bank reconciliation',
            ]
        ];

        foreach ($permissions as $module => $items) {
            foreach ($items as $name => $value) {
                if (is_array($value)) {
                    $description  = $value[0];
                    $isPrincipal  = $value['is_principle'] ?? false;
                } else {
                    $description  = $value;
                    $isPrincipal  = false;
                }

                Permission::query()->updateOrCreate(
                    ['name' => $name],
                    [
                        'module'       => $module,
                        'description'  => $description,
                        'is_principle' => $isPrincipal,
                    ]
                );
            }
        }
    }
}
