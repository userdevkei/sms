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
        Schema::create('admission_level_settings', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('education_level_id', 12)->unique();
            $t->boolean('is_open')->default(true);
            $t->boolean('requires_interview')->default(false);
            $t->text('instructions')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_level_settings');
    }
};
