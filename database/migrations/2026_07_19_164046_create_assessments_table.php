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
        Schema::create('assessments', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('name');                  // e.g. "Mid-Term CAT", "End Term Exam"
            $table->string('learning_area_id', 12);
            $table->string('stream_id', 12);
            $table->string('academic_term_id', 12);
            $table->string('assessment_type_id', 12);
            $table->decimal('max_score', 6, 2)->nullable(); // null when the type is competency-scored
            $table->date('assessment_date')->nullable();
            // draft: not yet open for marks entry | open: teacher can enter marks
            // | locked: marks entry closed, ready to feed subject results | void: cancelled
            $table->enum('status', ['draft', 'open', 'locked', 'void'])->default('draft');
            $table->string('created_by', 12);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('learning_area_id')->references('id')->on('learning_areas')->cascadeOnDelete();
            $table->foreign('stream_id')->references('id')->on('streams')->cascadeOnDelete();
            $table->foreign('academic_term_id')->references('id')->on('academic_terms')->cascadeOnDelete();
            $table->foreign('assessment_type_id')->references('id')->on('assessment_types')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['stream_id', 'academic_term_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
