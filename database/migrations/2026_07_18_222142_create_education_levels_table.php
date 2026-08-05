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
        Schema::create('education_levels', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('name', 100)->unique();       // Pre-Primary, Lower Primary, Upper Primary, Junior Secondary, Senior Secondary
            $table->string('code', 10)->unique();    // PP, LP, UP, JS, SS
            $table->unsignedInteger('sequence')->unique(); // 1..5 — drives progression order
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
        Schema::dropIfExists('education_levels');
    }
};
