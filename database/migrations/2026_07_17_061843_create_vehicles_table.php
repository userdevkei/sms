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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('registration_number', 50)->unique();
            $table->string('make', 50)->nullable();
            $table->string('model', 50)->nullable();
            $table->year('year_of_manufacture')->nullable();
            $table->unsignedInteger('capacity');
            $table->string('color', 100)->nullable();
            $table->string('logbook_number', 100)->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('inspection_expiry')->nullable(); // NTSA inspection
            $table->enum('status', ['active', 'under_maintenance', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
