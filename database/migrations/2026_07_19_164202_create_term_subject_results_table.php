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
        Schema::create('term_subject_results', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('student_enrollment_id', 12);
            $table->string('learning_area_id', 12);
            $table->string('academic_term_id', 12);
            $table->decimal('average_score', 6, 2)->nullable();
            $table->string('letter_grade', 5)->nullable();
            $table->enum('competency_level', ['EE', 'ME', 'AE', 'BE'])->nullable();
            $table->text('teacher_remarks')->nullable();
            $table->string('finalized_by', 12)->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_enrollment_id')->references('id')->on('student_enrollments')->cascadeOnDelete();
            $table->foreign('learning_area_id')->references('id')->on('learning_areas')->cascadeOnDelete();
            $table->foreign('academic_term_id')->references('id')->on('academic_terms')->cascadeOnDelete();
            $table->foreign('finalized_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['student_enrollment_id', 'learning_area_id', 'academic_term_id'], 'term_subject_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_subject_results');
    }
};
