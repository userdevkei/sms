<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('hostel_id', 12);
            $table->string('name', 100); // e.g. "Room 12", "Dorm A"
            $table->unsignedInteger('capacity'); // number of beds
            $table->decimal('fee_per_term', 10, 2)->nullable(); // overrides hostel default_fee_per_term if set
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('hostel_id')->references('id')->on('hostels')->cascadeOnDelete();
            $table->unique(['hostel_id', 'name']); // no duplicate room names within the same hostel
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
