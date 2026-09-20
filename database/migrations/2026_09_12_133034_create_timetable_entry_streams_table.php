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
        Schema::create('timetable_entry_streams', function (Blueprint $table) {
            $table->string('timetable_entry_id', 12);
            $table->foreign('timetable_entry_id')->references('id')->on('timetable_entries')->cascadeOnDelete();
            $table->string('stream_id', 12);
            $table->foreign('stream_id')->references('id')->on('streams')->cascadeOnDelete();
            $table->primary(['timetable_entry_id', 'stream_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_entry_streams');
    }
};
