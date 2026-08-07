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
        Schema::create('grade_level_learning_area', function (Blueprint $table) {
            $table->string('grade_level_id', 12);
            $table->string('learning_area_id', 12);
            $table->timestamps();

            $table->primary(['grade_level_id', 'learning_area_id']);
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->cascadeOnDelete();
            $table->foreign('learning_area_id')->references('id')->on('learning_areas')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_level_learning_areas');
    }
};
