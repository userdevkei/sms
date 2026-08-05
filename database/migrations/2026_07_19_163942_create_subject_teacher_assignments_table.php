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
        Schema::create('subject_teacher_assignments', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('user_id', 12);          // the teacher
            $table->string('learning_area_id', 12);
            $table->string('stream_id', 12);
            $table->string('academic_year', 9);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('learning_area_id')->references('id')->on('learning_areas')->cascadeOnDelete();
            $table->foreign('stream_id')->references('id')->on('streams')->cascadeOnDelete();
            $table->unique(['learning_area_id', 'stream_id', 'academic_year'], 'subj_stream_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_teacher_assignments');
    }
};
