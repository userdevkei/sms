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
        Schema::create('roles', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('name', 50)->unique();       // e.g. "Finance Officer / Bursar"
            $table->string('slug', 100)->unique();        // e.g. "finance_officer"
            $table->string('description', 150)->nullable();
            $table->boolean('is_system')->default(false); // guards core roles from deletion in UI
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
