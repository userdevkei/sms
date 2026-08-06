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
            'students' => [
                'students.view'   => 'View student records',
                'students.create' => 'Add students (manual)',
                'students.update' => 'Edit student records',
                'students.import' => 'Bulk import students via Excel',
                'students.delete' => 'Archive/delete student records',
                'students.manage' => 'Add, edit, import, and delete students',
            ],
            'fee_structures' => [
                'fee_structures.view'    => 'View fee structures',
                'fee_structures.create'  => 'Create fee structures (draft)',
                'fee_structures.update'  => 'Edit fee structures (draft)',
                'fee_structures.approve' => 'Approve/publish fee structure versions',
            ],
            'invoices' => [
                'invoices.view'   => 'View invoices',
                'invoices.create' => 'Generate term/supplementary invoices',
                'invoices.update' => 'Adjust invoices',
                'my-statement.view'   => 'View my fees statements',
            ],
            'payments' => [
                'payments.view'      => 'View payments',
                'payments.record'    => 'Record cash/manual payments',
                'payments.reconcile' => 'Reconcile M-Pesa/bank transactions',
                'my-payments.view'   => 'View my fees payments',
            ],
            'exemptions' => [
                'exemptions.view'    => 'View exemptions/scholarships',
                'exemptions.apply'   => 'Apply/recommend exemptions',
                'exemptions.approve' => 'Approve exemptions',
            ],
            'other_charges' => [
                'other_charges.view'   => 'View other charges',
                'other_charges.manage' => 'Create/edit other charges',
            ],
            'results' => [
                'results.view'        => 'View results',
                'results.enter_marks' => 'Enter marks/CATs (own subjects)',
                'results.approve'     => 'Approve results (own class/school)',
                'results.publish'     => 'Publish results to parents/students',
                'my-results.view'     => 'View my exam results',
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
            ],
            'system' => [
                'users.view'      => 'View system users',
                'users.manage'    => 'Create/edit/deactivate users',
                'roles.manage'    => 'Manage roles & permissions',
                'settings.manage' => 'Manage system settings',
                'admin.view'      => 'View system admin functions',
            ],
        ];

        foreach ($permissions as $module => $items) {
            foreach ($items as $name => $description) {
                Permission::query()->updateOrCreate(
                    ['name' => $name],
                    ['module' => $module, 'description' => $description]
                );
            }
        }
    }
}
