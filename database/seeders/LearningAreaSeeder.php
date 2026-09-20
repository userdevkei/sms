<?php

namespace Database\Seeders;

use App\Models\LearningArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningAreaSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | 1. LEARNING AREAS
            |--------------------------------------------------------------------------
            |
            | All learning areas used by PP1 - Grade 12.
            |
            */

            $learningAreas = [

                /*
                |--------------------------------------------------------------------------
                | PP1 - PP2
                |--------------------------------------------------------------------------
                */
                'Language Activities',
                'Mathematical Activities',
                'Environmental Activities',
                'Psychomotor and Creative Activities',
                'Religious Activities',

                /*
                |--------------------------------------------------------------------------
                | Grade 1 - Grade 3
                |--------------------------------------------------------------------------
                */
                'English',
                'Kiswahili',
                'Mathematics',
                'Environmental Activities',
                'Creative Activities',
                'Religious Education',

                /*
                |--------------------------------------------------------------------------
                | Grade 4 - Grade 6
                |--------------------------------------------------------------------------
                */
                'Science and Technology',
                'Social Studies',
                'Agriculture',
                'Creative Arts',
                'Life Skills Education',

                /*
                |--------------------------------------------------------------------------
                | Grade 7 - Grade 9
                |--------------------------------------------------------------------------
                */
                'Integrated Science',
                'Business Studies',
                'Computer Science',
                'Creative Arts and Sports',
                'Pre-Technical Studies',

                /*
                |--------------------------------------------------------------------------
                | Senior School - Core
                |--------------------------------------------------------------------------
                */
                'Core Mathematics',
                'Essential Mathematics',
                'Community Service Learning',

                /*
                |--------------------------------------------------------------------------
                | Senior School - Languages
                |--------------------------------------------------------------------------
                */
                'Literature in English',
                'Fasihi ya Kiswahili',
                'Indigenous Languages',
                'Sign Language',
                'Arabic',
                'French',
                'German',
                'Mandarin Chinese',

                /*
                |--------------------------------------------------------------------------
                | Senior School - Humanities / Social Sciences
                |--------------------------------------------------------------------------
                */
                'History and Citizenship',
                'Geography',

                /*
                |--------------------------------------------------------------------------
                | Senior School - Sciences
                |--------------------------------------------------------------------------
                */
                'Biology',
                'Chemistry',
                'Physics',
                'General Science',

                /*
                |--------------------------------------------------------------------------
                | Senior School - Applied Sciences / Technical
                |--------------------------------------------------------------------------
                */
                'Computer Studies',
                'Home Science',
                'Agriculture',
                'Aviation',
                'Building Construction',
                'Electricity',
                'Metalwork',
                'Power Mechanics',
                'Woodwork',
                'Media Technology',
                'Marine and Fisheries Technology',

                /*
                |--------------------------------------------------------------------------
                | Senior School - Arts & Sports
                |--------------------------------------------------------------------------
                */
                'Fine Arts',
                'Music and Dance',
                'Theatre and Film',
                'Sports and Recreation',
                'Physical Education',

                /*
                |--------------------------------------------------------------------------
                | Senior School - Religious Education
                |--------------------------------------------------------------------------
                */
                'Christian Religious Education',
                'Islamic Religious Education',
                'Hindu Religious Education',
            ];

            /*
             * Remove duplicates while preserving order.
             */
            $learningAreas = collect($learningAreas)
                ->map(fn ($name) => trim($name))
                ->filter()
                ->unique()
                ->values();

            /*
             * Create missing learning areas.
             *
             * If your table has additional required columns, add them here.
             */
            foreach ($learningAreas as $name) {
                LearningArea::query()->firstOrCreate(['name' => $name], ['is_compulsory' => true, 'status' => 'active']);
            }

            /*
            |--------------------------------------------------------------------------
            | 2. GET IDS
            |--------------------------------------------------------------------------
            */

            $learningAreaIds = DB::table('learning_areas')
                ->pluck('id', 'name');

            $gradeLevelIds = DB::table('grade_levels')
                ->pluck('id', 'code');

            $pathwayIds = DB::table('pathways')
                ->pluck('id', 'code');

            /*
            |--------------------------------------------------------------------------
            | 3. GRADE -> LEARNING AREAS
            |--------------------------------------------------------------------------
            */

            $gradeLearningAreas = [

                /*
                |--------------------------------------------------------------------------
                | PP1
                |--------------------------------------------------------------------------
                */
                'PP1' => [
                    'Language Activities',
                    'Mathematical Activities',
                    'Environmental Activities',
                    'Psychomotor and Creative Activities',
                    'Religious Activities',
                ],

                /*
                |--------------------------------------------------------------------------
                | PP2
                |--------------------------------------------------------------------------
                */
                'PP2' => [
                    'Language Activities',
                    'Mathematical Activities',
                    'Environmental Activities',
                    'Psychomotor and Creative Activities',
                    'Religious Activities',
                ],

                /*
                |--------------------------------------------------------------------------
                | Grade 1
                |--------------------------------------------------------------------------
                */
                'G1' => [
                    'English',
                    'Kiswahili',
                    'Mathematics',
                    'Environmental Activities',
                    'Creative Activities',
                    'Religious Education',
                ],

                /*
                |--------------------------------------------------------------------------
                | Grade 2
                |--------------------------------------------------------------------------
                */
                'G2' => [
                    'English',
                    'Kiswahili',
                    'Mathematics',
                    'Environmental Activities',
                    'Creative Activities',
                    'Religious Education',
                ],

                /*
                |--------------------------------------------------------------------------
                | Grade 3
                |--------------------------------------------------------------------------
                */
                'G3' => [
                    'English',
                    'Kiswahili',
                    'Mathematics',
                    'Environmental Activities',
                    'Creative Activities',
                    'Religious Education',
                ],

                /*
                |--------------------------------------------------------------------------
                | Grade 4
                |--------------------------------------------------------------------------
                */
                'G4' => [
                    'English',
                    'Kiswahili',
                    'Mathematics',
                    'Science and Technology',
                    'Social Studies',
                    'Agriculture',
                    'Creative Arts',
                    'Religious Education',
                    'Life Skills Education',
                ],

                /*
                |--------------------------------------------------------------------------
                | Grade 5
                |--------------------------------------------------------------------------
                */
                'G5' => [
                    'English',
                    'Kiswahili',
                    'Mathematics',
                    'Science and Technology',
                    'Social Studies',
                    'Agriculture',
                    'Creative Arts',
                    'Religious Education',
                    'Life Skills Education',
                ],

                /*
                |--------------------------------------------------------------------------
                | Grade 6
                |--------------------------------------------------------------------------
                */
                'G6' => [
                    'English',
                    'Kiswahili',
                    'Mathematics',
                    'Science and Technology',
                    'Social Studies',
                    'Agriculture',
                    'Creative Arts',
                    'Religious Education',
                    'Life Skills Education',
                ],

                /*
                |--------------------------------------------------------------------------
                | Grade 7
                |--------------------------------------------------------------------------
                */
                'G7' => [
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
                ],

                /*
                |--------------------------------------------------------------------------
                | Grade 8
                |--------------------------------------------------------------------------
                */
                'G8' => [
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
                ],

                /*
                |--------------------------------------------------------------------------
                | Grade 9
                |--------------------------------------------------------------------------
                */
                'G9' => [
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
                ],

                /*
                |--------------------------------------------------------------------------
                | SENIOR SCHOOL
                |--------------------------------------------------------------------------
                |
                | These are the learning areas available at Senior School level.
                |
                | Pathway-specific filtering is handled separately below.
                |--------------------------------------------------------------------------
                */

                'G10' => [
                    'English',
                    'Kiswahili',
                    'Core Mathematics',
                    'Essential Mathematics',
                    'Community Service Learning',

                    // Languages
                    'Literature in English',
                    'Fasihi ya Kiswahili',
                    'Indigenous Languages',
                    'Sign Language',
                    'Arabic',
                    'French',
                    'German',
                    'Mandarin Chinese',

                    // Social Sciences
                    'History and Citizenship',
                    'Geography',
                    'Business Studies',

                    // Sciences
                    'Biology',
                    'Chemistry',
                    'Physics',
                    'General Science',

                    // Applied / Technical
                    'Agriculture',
                    'Computer Studies',
                    'Home Science',
                    'Aviation',
                    'Building Construction',
                    'Electricity',
                    'Metalwork',
                    'Power Mechanics',
                    'Woodwork',
                    'Media Technology',
                    'Marine and Fisheries Technology',

                    // Arts & Sports
                    'Fine Arts',
                    'Music and Dance',
                    'Theatre and Film',
                    'Sports and Recreation',
                    'Physical Education',

                    // Religious Education
                    'Christian Religious Education',
                    'Islamic Religious Education',
                    'Hindu Religious Education',
                ],

                'G11' => [
                    'English',
                    'Kiswahili',
                    'Core Mathematics',
                    'Essential Mathematics',
                    'Community Service Learning',

                    'Literature in English',
                    'Fasihi ya Kiswahili',
                    'Indigenous Languages',
                    'Sign Language',
                    'Arabic',
                    'French',
                    'German',
                    'Mandarin Chinese',

                    'History and Citizenship',
                    'Geography',
                    'Business Studies',

                    'Biology',
                    'Chemistry',
                    'Physics',
                    'General Science',

                    'Agriculture',
                    'Computer Studies',
                    'Home Science',
                    'Aviation',
                    'Building Construction',
                    'Electricity',
                    'Metalwork',
                    'Power Mechanics',
                    'Woodwork',
                    'Media Technology',
                    'Marine and Fisheries Technology',

                    'Fine Arts',
                    'Music and Dance',
                    'Theatre and Film',
                    'Sports and Recreation',
                    'Physical Education',

                    'Christian Religious Education',
                    'Islamic Religious Education',
                    'Hindu Religious Education',
                ],

                'G12' => [
                    'English',
                    'Kiswahili',
                    'Core Mathematics',
                    'Essential Mathematics',
                    'Community Service Learning',

                    'Literature in English',
                    'Fasihi ya Kiswahili',
                    'Indigenous Languages',
                    'Sign Language',
                    'Arabic',
                    'French',
                    'German',
                    'Mandarin Chinese',

                    'History and Citizenship',
                    'Geography',
                    'Business Studies',

                    'Biology',
                    'Chemistry',
                    'Physics',
                    'General Science',

                    'Agriculture',
                    'Computer Studies',
                    'Home Science',
                    'Aviation',
                    'Building Construction',
                    'Electricity',
                    'Metalwork',
                    'Power Mechanics',
                    'Woodwork',
                    'Media Technology',
                    'Marine and Fisheries Technology',

                    'Fine Arts',
                    'Music and Dance',
                    'Theatre and Film',
                    'Sports and Recreation',
                    'Physical Education',

                    'Christian Religious Education',
                    'Islamic Religious Education',
                    'Hindu Religious Education',
                ],
            ];

            /*
            |--------------------------------------------------------------------------
            | 4. POPULATE GRADE LEVEL PIVOT
            |--------------------------------------------------------------------------
            */

            foreach ($gradeLearningAreas as $gradeCode => $subjects) {

                if (! isset($gradeLevelIds[$gradeCode])) {
                    $this->command?->warn(
                        "Grade level {$gradeCode} was not found."
                    );

                    continue;
                }

                $gradeLevelId = $gradeLevelIds[$gradeCode];

                foreach ($subjects as $subject) {

                    if (! isset($learningAreaIds[$subject])) {
                        $this->command?->warn(
                            "Learning area '{$subject}' was not found."
                        );

                        continue;
                    }

                    DB::table('grade_level_learning_area')->updateOrInsert(
                        [
                            'grade_level_id' => $gradeLevelId,
                            'learning_area_id' => $learningAreaIds[$subject],
                        ],
                        [
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 5. PATHWAY LEARNING AREAS
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | G10/G11/G12 are NOT duplicated here.
            |
            | The pathway determines which Senior School learning areas
            | are available to the learner.
            |--------------------------------------------------------------------------
            */

            $pathways = [

                /*
                |--------------------------------------------------------------------------
                | STEM
                |--------------------------------------------------------------------------
                */
                'STEM' => [

                    'Core Mathematics',
                    'Essential Mathematics',
                    'General Science',
                    'Biology',
                    'Chemistry',
                    'Physics',

                    'Agriculture',
                    'Computer Studies',
                    'Home Science',

                    'Aviation',
                    'Building Construction',
                    'Electricity',
                    'Metalwork',
                    'Power Mechanics',
                    'Woodwork',
                    'Media Technology',
                    'Marine and Fisheries Technology',
                ],

                /*
                |--------------------------------------------------------------------------
                | SOCIAL SCIENCES
                |--------------------------------------------------------------------------
                */
                'SOC' => [

                    'English',
                    'Kiswahili',

                    'Literature in English',
                    'Fasihi ya Kiswahili',

                    'Indigenous Languages',
                    'Sign Language',

                    'Arabic',
                    'French',
                    'German',
                    'Mandarin Chinese',

                    'History and Citizenship',
                    'Geography',
                    'Business Studies',

                    'Christian Religious Education',
                    'Islamic Religious Education',
                    'Hindu Religious Education',
                ],

                /*
                |--------------------------------------------------------------------------
                | ARTS & SPORTS SCIENCE
                |--------------------------------------------------------------------------
                */
                'ARTS' => [

                    'Fine Arts',
                    'Music and Dance',
                    'Theatre and Film',
                    'Sports and Recreation',
                    'Physical Education',
                ],
            ];

            /*
            |--------------------------------------------------------------------------
            | 6. POPULATE PATHWAY PIVOT
            |--------------------------------------------------------------------------
            */

            foreach ($pathways as $pathwayCode => $subjects) {

                if (! isset($pathwayIds[$pathwayCode])) {
                    $this->command?->warn(
                        "Pathway {$pathwayCode} was not found."
                    );

                    continue;
                }

                $pathwayId = $pathwayIds[$pathwayCode];

                foreach ($subjects as $subject) {

                    if (! isset($learningAreaIds[$subject])) {
                        $this->command?->warn(
                            "Learning area '{$subject}' was not found."
                        );

                        continue;
                    }

                    DB::table('pathway_learning_area')->updateOrInsert(
                        [
                            'pathway_id' => $pathwayId,
                            'learning_area_id' => $learningAreaIds[$subject],
                        ],
                        [
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }

            $this->command?->info(
                'Learning areas, grade mappings and pathway mappings seeded successfully.'
            );
        });
    }
}
