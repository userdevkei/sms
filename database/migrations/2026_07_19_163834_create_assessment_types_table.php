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
        Schema::create('assessment_types', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('name')->unique(); // Formative, Summative, Project/Practical, Portfolio, CAT, End of Term Exam, National Assessment
            $table->enum('scoring_mode', ['score', 'competency']); // drives whether marks entry expects a number or a CBC rating
            $table->unsignedInteger('default_max_score')->nullable(); // only meaningful when scoring_mode = score
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_types');
    }
};
