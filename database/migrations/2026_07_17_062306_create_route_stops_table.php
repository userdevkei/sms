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
        Schema::create('route_stops', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('route_id', 12);
            $table->string('name', 150); // stop/landmark name
            $table->unsignedInteger('sequence'); // order along the route
            $table->string('landmark_description', 200)->nullable();
            // ASSUMPTION: this is a per-term fare (matches the finance module's
            // termly invoicing pattern), not a per-trip fare. Adjust if your
            // school charges transport differently.
            $table->decimal('fare', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('route_id')->references('id')->on('transport_routes')->cascadeOnDelete();
            $table->index(['route_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
