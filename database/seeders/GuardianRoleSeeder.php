<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class GuardianRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['slug' => 'guardian'], ['description' => 'Parent / guardian portal', 'name' => 'Guardian']);

        $ids = [];
        foreach ([
                     ['name' => 'guardian.view', 'module' => 'guardian', 'is_principle' => true, 'description' => 'Guardian can access the guardian dashboard'],
                     ['name' => 'guardian.pay', 'module' => 'guardian', 'is_principle' => true, 'description' => 'Guardians can make payments for students'],
                     ['name' => 'guardian.update_student', 'module' => 'guardian', 'is_principle' => true, 'description'=> 'Guardians are allowed to update student details']] as $name) {
            $ids[] = Permission::firstOrCreate($name)->id;
        }

        $role->permissions()->syncWithoutDetaching($ids);
    }
}
