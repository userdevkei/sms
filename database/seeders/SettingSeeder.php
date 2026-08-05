<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'school_name'     => ['value' => 'Your School Name',            'group' => 'branding'],
            'tagline'         => ['value' => 'Nurturing Excellence',        'group' => 'branding'],
            'motto'           => ['value' => 'Knowledge, Character, Service','group' => 'branding'],
            'logo_path'       => ['value' => null,      'group' => 'branding'],
            'favicon_path'    => ['value' => null,      'group' => 'branding'],
            'primary_color'   => ['value' => '#0B3D62', 'group' => 'theme'],
            'secondary_color' => ['value' => '#0E8388', 'group' => 'theme'],
            'sidebar_color'   => ['value' => '#0B3D62', 'group' => 'theme'],
            'address'         => ['value' => null,      'group' => 'contact'],
            'phone'           => ['value' => null,      'group' => 'contact'],
            'email'           => ['value' => null,      'group' => 'contact'],
            'currency'        => ['value' => 'KES',     'group' => 'general'],
        ];

        foreach ($defaults as $key => $data) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $data['value'], 'group' => $data['group'], 'type' => 'string']
            );
        }
    }
}
