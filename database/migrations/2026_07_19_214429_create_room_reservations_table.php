<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_reservations', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('user_id', 12);            // the student
            $table->string('hostel_id', 12);            // requested hostel
            $table->string('preferred_room_id', 12)->nullable(); // optional preference, not a guarantee
            $table->string('academic_year', 9);
            $table->string('term', 50)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'allocated', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('requested_by', 12);       // staff member who logged the request
            $table->string('reviewed_by', 12)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('hostel_id')->references('id')->on('hostels')->cascadeOnDelete();
            $table->foreign('preferred_room_id')->references('id')->on('rooms')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_reservations');
    }
};
