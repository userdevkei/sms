<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('timetable_id', 12);
            $table->foreign('timetable_id')->references('id')->on('timetables')->cascadeOnDelete();
            $table->string('time_slot_id', 12);
            $table->foreign('time_slot_id')->references('id')->on('time_slots')->restrictOnDelete();
            $table->string('grade_level_id', 12);
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->restrictOnDelete();
            $table->string('learning_area_id', 12);
            $table->foreign('learning_area_id')->references('id')->on('learning_areas')->restrictOnDelete();
            $table->string('subject_teacher_assignment_id', 12)->nullable();
            $table->foreign('subject_teacher_assignment_id')->references('id')->on('subject_teacher_assignments')->nullOnDelete();
            $table->string('teacher_id', 12)->nullable(); // denormalized for fast conflict queries
            $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
            $table->boolean('is_double')->default(false);
            $table->string('double_group_id', 36)->nullable(); // links the two halves of a double lesson
            $table->boolean('locked')->default(false); // protects manual edits from being wiped on regenerate
            $table->string('source')->default('generated'); // generated|manual
            $table->timestamps();
            $table->softDeletes();

            $table->index(['timetable_id', 'time_slot_id']);
            $table->index(['teacher_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
    }
};
