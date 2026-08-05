<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_allocations', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('user_id', 12);           // the student
            $table->string('room_id', 12);
            $table->string('reservation_id', 12)->nullable(); // set if this came from an approved reservation
            $table->string('academic_year', 9);
            $table->string('term', 50)->nullable();
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->date('allocated_on')->nullable();
            $table->date('vacated_on')->nullable();
            $table->string('allocated_by', 12);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
            $table->foreign('reservation_id')->references('id')->on('room_reservations')->nullOnDelete();
            $table->foreign('allocated_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['room_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_allocations');
    }
};
