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
        Schema::create('term_overall_results', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('student_enrollment_id', 12);
            $table->string('academic_term_id', 12);
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('average_score', 6, 2)->nullable();
            $table->unsignedInteger('position_in_stream')->nullable();
            $table->unsignedInteger('stream_size')->nullable();
            $table->unsignedInteger('position_in_grade')->nullable();
            $table->unsignedInteger('grade_size')->nullable();
            $table->text('class_teacher_remarks')->nullable();
            $table->text('principal_remarks')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->string('published_by', 12)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_enrollment_id')->references('id')->on('student_enrollments')->cascadeOnDelete();
            $table->foreign('academic_term_id')->references('id')->on('academic_terms')->cascadeOnDelete();
            $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['student_enrollment_id', 'academic_term_id'], 'term_overall_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_overall_results');
    }
};
