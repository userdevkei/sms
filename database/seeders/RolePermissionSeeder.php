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
                'admin.view'
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
                'reports.finance',
            ],

            'teacher' => [
                'curriculum.view',
                'results.view',
                'results.enter_marks',
                'students.view',
            ],

            'class_teacher' => [
                'results.view',
                'results.enter_marks',
                'results.approve',
                'students.view',
                'progression.initiate',
                'reports.academic',
            ],

            'parent' => [
                'admissions.create',
                'admissions.view',
                'invoices.view',
                'payments.record',
                'results.view',
                'students.view',
            ],

            'student' => [
                'my-results.view',
                'my-statement.view',
                'my-payments.view',
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
