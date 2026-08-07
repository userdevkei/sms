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
        Schema::create('pathway_learning_area', function (Blueprint $table) {
            $table->string('pathway_id', 12);
            $table->string('learning_area_id', 12);
            $table->timestamps();

            $table->primary(['pathway_id', 'learning_area_id']);
            $table->foreign('pathway_id')->references('id')->on('pathways')->cascadeOnDelete();
            $table->foreign('learning_area_id')->references('id')->on('learning_areas')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pathway_learning_areas');
    }
};
