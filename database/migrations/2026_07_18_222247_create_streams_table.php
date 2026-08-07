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
        Schema::create('streams', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('grade_level_id', 12);
            $table->string('pathway_id', 12)->nullable(); // only meaningful for Senior Secondary streams
            $table->string('name', 50);                        // East, Eagles, A, ...
            $table->unsignedInteger('capacity')->nullable();
            $table->string('class_teacher_id', 12)->nullable(); // -> users.id
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->cascadeOnDelete();
            $table->foreign('pathway_id')->references('id')->on('pathways')->nullOnDelete();
            $table->foreign('class_teacher_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['grade_level_id', 'name']); // no duplicate stream names within the same grade
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
