<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin',                       'slug' => 'super_admin',          'is_system' => true],
            ['name' => 'Admin / Principal / Director',      'slug' => 'admin',                'is_system' => true],
            ['name' => 'Registrar (Admissions)',            'slug' => 'registrar',            'is_system' => true],
            ['name' => 'Finance Officer / Bursar',          'slug' => 'finance_officer',      'is_system' => true],
            ['name' => 'Teacher',                           'slug' => 'teacher',              'is_system' => true],
            ['name' => 'Class / Head Teacher',              'slug' => 'class_teacher',        'is_system' => true],
            ['name' => 'Parent / Guardian',                 'slug' => 'parent',               'is_system' => true],
            ['name' => 'Student',                           'slug' => 'student',              'is_system' => true],
            ['name' => 'Driver',                            'slug' => 'driver',               'is_system' => true],
            ['name' => 'Hostel Warden / Accommodation Officer', 'slug' => 'hostel_warden',    'is_system' => true],
            ['name' => 'HR Officer',                        'slug' => 'hr_officer',           'is_system' => true],
            ['name' => 'Accountant',                        'slug' => 'accountant',           'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name'], 'is_system' => $role['is_system']]
            );
        }
    }
}
