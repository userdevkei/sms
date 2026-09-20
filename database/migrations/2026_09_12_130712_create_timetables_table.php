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
        Schema::create('timetables', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('name')->nullable();
            $table->string('academic_year', 9);
            $table->unsignedTinyInteger('term');
            $table->string('status')->default('draft'); // draft|approved|published
            $table->timestamp('generated_at')->nullable();
            $table->string('generated_by', 12)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by', 12)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('published_by', 12)->nullable();
            $table->json('generation_notes')->nullable(); // conflicts/warnings from the last run
            $table->timestamps();
            $table->softDeletes();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
