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
        Schema::create('lesson_requirements', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('grade_level_id', 12);
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->cascadeOnDelete();
            $table->string('learning_area_id', 12);
            $table->foreign('learning_area_id')->references('id')->on('learning_areas')->cascadeOnDelete();
            $table->unsignedTinyInteger('lessons_per_week')->default(5);
            $table->unsignedTinyInteger('double_lessons_per_week')->default(0); // e.g. 1 means one of the weekly slots is a double
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['grade_level_id', 'learning_area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_requirements');
    }
};
