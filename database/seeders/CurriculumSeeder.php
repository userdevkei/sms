<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use App\Models\GradeLevel;
use App\Models\LearningArea;
use App\Models\Pathway;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'Pre-Primary',       'code' => 'PP', 'sequence' => 1],
            ['name' => 'Lower Primary',     'code' => 'LP', 'sequence' => 2],
            ['name' => 'Upper Primary',     'code' => 'UP', 'sequence' => 3],
            ['name' => 'Junior Secondary',  'code' => 'JS', 'sequence' => 4],
            ['name' => 'Senior Secondary',  'code' => 'SS', 'sequence' => 5],
        ];

        $levelModels = [];
        foreach ($levels as $level) {
            $levelModels[$level['code']] = EducationLevel::query()->updateOrCreate(
                ['code' => $level['code']],
                $level
            );
        }

        $grades = [
            ['name' => 'PP1',     'code' => 'PP1',  'level' => 'PP', 'sequence' => 1],
            ['name' => 'PP2',     'code' => 'PP2',  'level' => 'PP', 'sequence' => 2],
            ['name' => 'Grade 1', 'code' => 'G1',   'level' => 'LP', 'sequence' => 3],
            ['name' => 'Grade 2', 'code' => 'G2',   'level' => 'LP', 'sequence' => 4],
            ['name' => 'Grade 3', 'code' => 'G3',   'level' => 'LP', 'sequence' => 5],
            ['name' => 'Grade 4', 'code' => 'G4',   'level' => 'UP', 'sequence' => 6],
            ['name' => 'Grade 5', 'code' => 'G5',   'level' => 'UP', 'sequence' => 7],
            ['name' => 'Grade 6', 'code' => 'G6',   'level' => 'UP', 'sequence' => 8],
            ['name' => 'Grade 7', 'code' => 'G7',   'level' => 'JS', 'sequence' => 9],
            ['name' => 'Grade 8', 'code' => 'G8',   'level' => 'JS', 'sequence' => 10],
            ['name' => 'Grade 9', 'code' => 'G9',   'level' => 'JS', 'sequence' => 11],
            ['name' => 'Grade 10', 'code' => 'G10', 'level' => 'SS', 'sequence' => 12],
            ['name' => 'Grade 11', 'code' => 'G11', 'level' => 'SS', 'sequence' => 13],
            ['name' => 'Grade 12', 'code' => 'G12', 'level' => 'SS', 'sequence' => 14],
        ];

        foreach ($grades as $grade) {
            GradeLevel::query()->updateOrCreate(
                ['sequence' => $grade['sequence']],
                [
                    'education_level_id' => $levelModels[$grade['level']]->id,
                    'name' => $grade['name'],
                    'code' => $grade['code'],
                    'status' => 'active',
                ]
            );
        }

        $pathways = [
            ['name' => 'STEM',                    'code' => 'STEM', 'description' => 'Science, Technology, Engineering and Mathematics'],
            ['name' => 'Social Sciences',          'code' => 'SOC',  'description' => 'Humanities and social science subjects'],
            ['name' => 'Arts & Sports Science',    'code' => 'ARTS', 'description' => 'Creative arts, performing arts, and sports science'],
        ];

        foreach ($pathways as $pathway) {
            Pathway::query()->updateOrCreate(['code' => $pathway['code']], $pathway);
        }

        // A starter set of common learning areas — schools should complete
        // their own full subject list via the UI; this just seeds the basics
        // so the module isn't empty on first load.
        $subjects = ['English', 'Kiswahili', 'Mathematics', 'Science and Technology', 'Social Studies', 'Religious Education'];

        foreach ($subjects as $subject) {
            LearningArea::query()->firstOrCreate(['name' => $subject], ['is_compulsory' => true, 'status' => 'active']);
        }
    }
}
