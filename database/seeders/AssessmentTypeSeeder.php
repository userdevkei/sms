<?php

namespace Database\Seeders;

use App\Models\AssessmentType;
use Illuminate\Database\Seeder;

class AssessmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Formative Assessment (CAT)', 'scoring_mode' => 'score', 'default_max_score' => 30],
            ['name' => 'Summative Assessment (End Term Exam)', 'scoring_mode' => 'score', 'default_max_score' => 100],
            ['name' => 'Project-Based / Practical Assessment', 'scoring_mode' => 'competency'],
            ['name' => 'Portfolio Assessment', 'scoring_mode' => 'competency'],
            ['name' => 'Competency Rating (General)', 'scoring_mode' => 'competency'],
        ];

        foreach ($types as $type) {
            AssessmentType::query()->firstOrCreate(['name' => $type['name']], $type + ['status' => 'active']);
        }
    }
}
