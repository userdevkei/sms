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
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('user_id', 12);          // -> users.id (the student)
            $table->string('grade_level_id', 12);
            $table->string('stream_id', 12)->nullable();
            $table->string('pathway_id', 12)->nullable();
            $table->string('academic_year', 9);      // e.g. "2026"
            $table->enum('status', ['active', 'promoted', 'repeated', 'transferred_out', 'withdrawn', 'deceased', 'graduated'])->default('active');
            $table->date('enrolled_on')->nullable();
            $table->date('exited_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->cascadeOnDelete();
            $table->foreign('stream_id')->references('id')->on('streams')->nullOnDelete();
            $table->foreign('pathway_id')->references('id')->on('pathways')->nullOnDelete();
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
