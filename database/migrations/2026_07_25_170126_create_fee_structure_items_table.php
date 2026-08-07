<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structure_items', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('fee_structure_id', 12);
            $table->string('votehead_id', 12);
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->foreign('fee_structure_id')->references('id')->on('fee_structures')->cascadeOnDelete();
            $table->foreign('votehead_id')->references('id')->on('voteheads')->cascadeOnDelete();
            $table->unique(['fee_structure_id', 'votehead_id']); // no duplicate votehead within one structure
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_items');
    }
};
