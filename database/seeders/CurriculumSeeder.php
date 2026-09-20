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
            ['name' => 'Kindergarten',      'code' => 'KG', 'sequence' => 1],
            ['name' => 'Pre-Primary',       'code' => 'PP', 'sequence' => 2],
            ['name' => 'Lower Primary',     'code' => 'LP', 'sequence' => 3],
            ['name' => 'Upper Primary',     'code' => 'UP', 'sequence' => 4],
            ['name' => 'Junior Secondary',  'code' => 'JS', 'sequence' => 5],
            ['name' => 'Senior Secondary',  'code' => 'SS', 'sequence' => 6],
        ];

        $levelModels = [];
        foreach ($levels as $level) {
            $levelModels[$level['code']] = EducationLevel::query()->updateOrCreate(
                ['code' => $level['code']],
                $level
            );
        }

        $grades = [
            ['name' => 'KG1',     'code' => 'KG1',  'level' => 'KG', 'sequence' => 1],
            ['name' => 'KG2',     'code' => 'KG2',  'level' => 'KG', 'sequence' => 2],
            ['name' => 'KG3',     'code' => 'KG3',  'level' => 'KG', 'sequence' => 3],
            ['name' => 'PP1',     'code' => 'PP1',  'level' => 'PP', 'sequence' => 4],
            ['name' => 'PP2',     'code' => 'PP2',  'level' => 'PP', 'sequence' => 5],
            ['name' => 'Grade 1', 'code' => 'G1',   'level' => 'LP', 'sequence' => 6],
            ['name' => 'Grade 2', 'code' => 'G2',   'level' => 'LP', 'sequence' => 7],
            ['name' => 'Grade 3', 'code' => 'G3',   'level' => 'LP', 'sequence' => 8],
            ['name' => 'Grade 4', 'code' => 'G4',   'level' => 'UP', 'sequence' => 9],
            ['name' => 'Grade 5', 'code' => 'G5',   'level' => 'UP', 'sequence' => 10],
            ['name' => 'Grade 6', 'code' => 'G6',   'level' => 'UP', 'sequence' => 11],
            ['name' => 'Grade 7', 'code' => 'G7',   'level' => 'JS', 'sequence' => 12],
            ['name' => 'Grade 8', 'code' => 'G8',   'level' => 'JS', 'sequence' => 13],
            ['name' => 'Grade 9', 'code' => 'G9',   'level' => 'JS', 'sequence' => 14],
            ['name' => 'Grade 10', 'code' => 'G10', 'level' => 'SS', 'sequence' => 15],
            ['name' => 'Grade 11', 'code' => 'G11', 'level' => 'SS', 'sequence' => 16],
            ['name' => 'Grade 12', 'code' => 'G12', 'level' => 'SS', 'sequence' => 17],
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

        $subjects = [
            // =========================
            // PRE-PRIMARY
            // =========================
            'Language Activities',
            'Mathematical Activities',
            'Environmental Activities',
            'Psychomotor and Creative Activities',
            'Religious Activities',

            // =========================
            // LOWER PRIMARY - GRADE 1–3
            // =========================
            'English',
            'Kiswahili',
            'Mathematics',
            'Environmental Activities',
            'Creative Activities',
            'Religious Education',

            // =========================
            // UPPER PRIMARY - GRADE 4–6
            // =========================
            'English',
            'Kiswahili',
            'Mathematics',
            'Science and Technology',
            'Social Studies',
            'Agriculture',
            'Creative Arts',
            'Religious Education',
            'Life Skills Education',

            // =========================
            // JUNIOR SCHOOL - GRADE 7–9
            // =========================
            'English',
            'Kiswahili',
            'Mathematics',
            'Integrated Science',
            'Social Studies',
            'Business Studies',
            'Agriculture',
            'Computer Science',
            'Creative Arts and Sports',
            'Life Skills Education',
            'Religious Education',
            'Pre-Technical Studies',

            // =========================
            // SENIOR SCHOOL - STEM
            // GRADE 10–12
            // =========================
            'Core Mathematics',
            'Essential Mathematics',
            'General Science',
            'Biology',
            'Chemistry',
            'Physics',
            'Agriculture',
            'Computer Studies',
            'Home Science',

            // Technical and Applied Sciences
            'Aviation',
            'Building Construction',
            'Electricity',
            'Metalwork',
            'Power Mechanics',
            'Woodwork',
            'Media Technology',
            'Marine and Fisheries Technology',

            // =========================
            // SENIOR SCHOOL - SOCIAL SCIENCES
            // GRADE 10–12
            // =========================
            'English',
            'Literature in English',
            'Kiswahili',
            'Fasihi ya Kiswahili',
            'History and Citizenship',
            'Geography',
            'Business Studies',
            'Christian Religious Education',
            'Islamic Religious Education',
            'Hindu Religious Education',

            // Languages
            'Indigenous Languages',
            'Arabic',
            'French',
            'German',
            'Mandarin Chinese',
            'Sign Language',

            // =========================
            // SENIOR SCHOOL - ARTS & SPORTS SCIENCE
            // GRADE 10–12
            // =========================
            'Fine Arts',
            'Music and Dance',
            'Theatre and Film',
            'Sports and Recreation',
            'Physical Education',
        ];

        foreach ($subjects as $subject) {
            LearningArea::query()->firstOrCreate(['name' => $subject], ['is_compulsory' => true, 'status' => 'active']);
        }
    }
}
