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
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('education_level_id', 12);
            $table->string('name', 50);                  // PP1, Grade 1, Grade 7, ...
            $table->string('code', 10)->nullable();
            // Global sequence across ALL grade levels (not just within one education
            // level) — this is what the future Progression module walks along to
            // move a student from Grade 6 to Grade 7, across the level boundary.
            $table->unsignedInteger('sequence')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('education_level_id')->references('id')->on('education_levels')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
    }
};
