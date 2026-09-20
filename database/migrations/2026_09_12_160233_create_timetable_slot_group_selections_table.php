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
        Schema::create('timetable_slot_group_selections', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('timetable_id', 12);
            $table->foreign('timetable_id')->references('id')->on('timetables')->cascadeOnDelete();
            $table->string('education_level_id', 12);
            $table->foreign('education_level_id')->references('id')->on('education_levels')->cascadeOnDelete();
            $table->string('time_slot_group_id', 12);
            $table->foreign('time_slot_group_id')->references('id')->on('time_slot_groups')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

//            $table->unique(['timetable_id', 'education_level_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_slot_group_selections');
    }
};
