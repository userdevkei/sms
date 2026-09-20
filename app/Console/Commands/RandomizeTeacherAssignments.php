<?php

namespace App\Console\Commands;

use App\Models\SubjectTeacherAssignment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RandomizeTeacherAssignments extends Command
{
    protected $signature = 'assignments:randomize-teachers
                            {academic_year : e.g. 2026}
                            {--dry-run : Preview without writing anything}
                            {--fresh : Delete existing assignments for this academic year first}';

    protected $description = 'Randomly assign a teacher to every learning area for every stream, for a given academic year.';

    public function handle(): int
    {
        $academicYear = $this->argument('academic_year');
        $dryRun = $this->option('dry-run');

        $teacherIds = User::whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))
            ->pluck('id');

        if ($teacherIds->isEmpty()) {
            $this->error('No users with the Teacher role were found.');
            return self::FAILURE;
        }

        $this->info("Found {$teacherIds->count()} teacher(s) to assign from.");

        if ($this->option('fresh') && !$dryRun) {
            $deleted = DB::table('subject_teacher_assignments')
                ->where('academic_year', $academicYear)
                ->delete();
            $this->warn("Removed {$deleted} existing assignment(s) for {$academicYear}.");
        }

        $streams = DB::table('streams')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'grade_level_id', 'pathway_id']);

        $created = 0;
        $skipped = 0;

        foreach ($streams as $stream) {
            if ($stream->pathway_id) {
                $learningAreaIds = DB::table('pathway_learning_area')
                    ->where('pathway_id', $stream->pathway_id)
                    ->pluck('learning_area_id');
            } else {
                $learningAreaIds = DB::table('grade_level_learning_area')
                    ->where('grade_level_id', $stream->grade_level_id)
                    ->pluck('learning_area_id');
            }

            if ($learningAreaIds->isEmpty()) {
                $this->warn("Stream #{$stream->id} ({$stream->name}) has no learning areas mapped — skipped.");
                $skipped++;
                continue;
            }

            foreach ($learningAreaIds as $learningAreaId) {
                $exists = DB::table('subject_teacher_assignments')
                    ->where('stream_id', $stream->id)
                    ->where('learning_area_id', $learningAreaId)
                    ->where('academic_year', $academicYear)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $teacherId = $teacherIds->random();

                if ($dryRun) {
                    $this->line("Would assign teacher {$teacherId} → learning area {$learningAreaId} → stream {$stream->id} ({$stream->name})");
                } else {
                    SubjectTeacherAssignment::create([
                        'user_id' => $teacherId,
                        'learning_area_id' => $learningAreaId,
                        'stream_id' => $stream->id,
                        'academic_year' => $academicYear,
                        'status' => 'active',
                    ]);
                }

                $created++;
            }
        }

        $verb = $dryRun ? 'would be created' : 'created';
        $this->info(($dryRun ? '[DRY RUN] ' : '')."{$created} assignment(s) {$verb}, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
