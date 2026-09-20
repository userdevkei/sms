<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $map = [
            'super_admin' => ['*'],

            'admin' => [
                'branding.view',
                'curriculum.view',
                'fee_structures.approve',
                'admissions.view',
                'admissions.approve',
                'students.view',
                'invoices.view',
                'payments.view',
                'exemptions.approve',
                'results.view',
                'results.approve',
                'results.publish',
                'progression.view',
                'progression.approve',
                'reports.admissions',
                'reports.finance',
                'reports.academic',
                'reports.enrollment',
                'transport.view',
                'accommodation.view',
                'hr.view',
                'accounting.view',
                'admin.view',
                'timetable.view',
                'timetable.create',
                'timetable.edit',
                'timetable.delete',
                'timetable.manage',
                'time_slots.view',
                'time_slots.create',
                'time_slots.edit',
                'time_slots.delete',
            ],

            'registrar' => [
                'admissions.view',
                'admissions.create',
                'admissions.review',
                'admissions.approve',
                'students.view',
                'students.create',
                'students.import',
                'curriculum.view',
                'reports.admissions',
                'time_slots.view',
                'time_slots.create',
                'time_slots.edit',
                'timetable.view',
                'timetable.create',
                'timetable.edit',
                'timetable.manage',
            ],

            'finance_officer' => [
                'fee_structures.view',
                'fee_structures.create',
                'fee_structures.update',
                'invoices.view',
                'invoices.create',
                'invoices.update',
                'payments.view',
                'payments.record',
                'payments.reconcile',
                'exemptions.view',
                'exemptions.apply',
                'other_charges.view',
                'other_charges.manage',
                'students.view',
                'students.profile',
                'reports.finance',
                'accounting.view',
                'accounting.manage',
                'income.view',
                'income.create',
                'income.update',
                'income.manage',
                'expenses.view',
                'expenses.create',
                'expenses.update',
                'expenses.manage',
                'expense.categories.create',
                'expense.categories.update',
                'expense.categories.manage',
                'income.categories.create',
                'income.categories.update',
                'income.categories.manage',
                'transport.update.student-transport',
                'exemptions.manage',
                'voteheads.view',
                'voteheads.create',
                'voteheads.update',
                'other_charges_type.manage',
                'other_charges.manage',
                'other_charges_type.create',
                'other_charges_type.update',
                'other_charges.create',
                'bank_reconciliation.view',
                'bank_reconciliation.manage',
            ],

            'teacher' => [
                'curriculum.view',
                'results.view',
                'results.enter_marks',
                'students.view',
                'my-timetable.view',
                'timetable.view',
                'students.profile',
            ],

            'class_teacher' => [
                'results.view',
                'results.enter_marks',
                'results.approve',
                'students.view',
                'progression.initiate',
                'reports.academic',
                'my-timetable.view',
                'timetable.view',
                'my-timetable.class',
                'results.report_cards.view'
            ],

            'parent' => [
                'admissions.create',
                'admissions.view',
                'invoices.view',
                'payments.record',
                'results.view',
                'students.view',
                'my-timetable.view',
            ],

            'student' => [
                'my-results.view',
                'my-statement.view',
                'my-payments.view',
                'my-timetable.view',
                'timetable.view'
            ],

            'transport_coordinator' => [
                'transport.view',
                'transport.manage',
            ],

            'hostel_warden' => [
                'accommodation.view',
                'accommodation.manage',
            ],

            'hr_officer' => [
                'hr.view',
                'hr.manage',
            ],

            'accountant' => [
                'accounting.view',
                'accounting.manage',
                'reports.finance',
            ],
        ];

        // Student self-service permissions that should not be assigned to Super Admin
        $excludedSuperAdminPermissions = [
            'my-results.view',
            'my-statement.view',
            'my-payments.view',
            'my-timetable.view',
            'my-timetable.class'
        ];

        foreach ($map as $slug => $permissionNames) {
            $role = Role::where('slug', $slug)->first();

            if (! $role) {
                continue;
            }

            if ($permissionNames === ['*']) {
                $permissionIds = Permission::whereNotIn('name', $excludedSuperAdminPermissions)
                    ->pluck('id')
                    ->all();

                $role->permissions()->sync($permissionIds);

                continue;
            }

            $permissionIds = Permission::whereIn('name', $permissionNames)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }

        // Create Super Admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'password'   => Hash::make('password'),
                'status'     => 'active',
            ]
        );

        // Assign Super Admin role
        $role = Role::where('slug', 'super_admin')->first();

        if ($role) {
            RoleUser::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                ]
            );
        }
    }
}
