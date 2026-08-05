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
        Schema::create('vehicle_maintenance_logs', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('vehicle_id', 12);
            $table->date('service_date');
            $table->string('description', 255);
            $table->decimal('cost', 10, 2)->nullable();
            $table->unsignedInteger('odometer_reading')->nullable();
            $table->date('next_service_date')->nullable();
            $table->string('serviced_by', 255)->nullable(); // garage/mechanic name
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_logs');
    }
};
