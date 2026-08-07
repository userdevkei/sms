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
        Schema::create('grading_bands', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->string('letter_grade', 5);
            $table->decimal('points', 4, 2)->nullable(); // optional, for mean-grade/GPA-style calculations
            $table->string('remark')->nullable(); // e.g. "Excellent", "Good", "Needs Improvement"
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['min_score', 'max_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_bands');
    }
};
