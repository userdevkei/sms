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
        Schema::create('route_assignments', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('route_id', 12);
            $table->string('vehicle_id', 12);
            $table->string('driver_id', 12);
            // Free-text for now — becomes a real FK to an academic_terms table
            // once the Curriculum module defines terms formally.
            $table->string('term', 12)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->timestamps();

            $table->foreign('route_id')->references('id')->on('transport_routes')->cascadeOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('driver_id')->references('user_id')->on('drivers')->cascadeOnDelete();
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_assignments');
    }
};
