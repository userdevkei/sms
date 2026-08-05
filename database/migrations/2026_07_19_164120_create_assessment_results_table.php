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
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('assessment_id', 12);
            $table->string('student_enrollment_id', 12);
            $table->decimal('score', 6, 2)->nullable();          // when scoring_mode = score
            $table->enum('competency_level', ['EE', 'ME', 'AE', 'BE'])->nullable(); // when scoring_mode = competency
            $table->boolean('is_absent')->default(false);
            $table->text('remarks')->nullable();
            $table->string('entered_by', 12)->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('assessment_id')->references('id')->on('assessments')->cascadeOnDelete();
            $table->foreign('student_enrollment_id')->references('id')->on('student_enrollments')->cascadeOnDelete();
            $table->foreign('entered_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['assessment_id', 'student_enrollment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};
