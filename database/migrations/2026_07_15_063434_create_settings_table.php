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
        Schema::create('settings', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 200)->default('string'); // string, boolean, integer, json, file
            $table->string('group', 100)->default('general'); // branding, theme, contact, general
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
