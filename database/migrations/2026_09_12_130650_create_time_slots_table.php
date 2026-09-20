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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('time_slot_group_id', 12)->nullable()->after('id');
            $table->foreign('time_slot_group_id')->references('id')->on('time_slot_groups')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1=Mon ... 7=Sun
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type'); // main|remedial
            $table->string('label')->nullable(); // "Period 1", "Morning Remedial"
            $table->unsignedSmallInteger('sequence'); // ordering within the day; consecutive sequence = adjacent slot
            $table->boolean('is_break')->default(false); // e.g. tea break placeholder — breaks the "consecutive" chain for doubles
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['day_of_week', 'sequence']);
        });

        Schema::table('education_levels', function (Blueprint $table) {
            $table->string('default_time_slot_group_id', 12)->nullable()->after('sequence');
            $table->foreign('default_time_slot_group_id')->references('id')->on('time_slot_groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
