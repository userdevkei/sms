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
        Schema::create('timetable_grade_levels', function (Blueprint $table) {
            $table->string('timetable_id', 12);
            $table->foreign('timetable_id')->references('id')->on('timetables')->cascadeOnDelete();
            $table->string('grade_level_id', 12);
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->cascadeOnDelete();
            $table->primary(['timetable_id', 'grade_level_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_grade_levels');
    }
};
