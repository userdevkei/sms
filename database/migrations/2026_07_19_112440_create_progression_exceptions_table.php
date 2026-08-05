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
        Schema::create('progression_exceptions', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('user_id', 12);
            $table->string('enrollment_id', 12);
            $table->enum('type', ['repeat', 'transferred_out', 'withdrawn', 'deceased']);
            $table->text('reason');                 // mandatory — never optional for an exception
            $table->string('new_academic_year', 9)->nullable(); // required only when type = repeat
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('requested_by', 12);
            $table->string('reviewed_by', 12)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('enrollment_id')->references('id')->on('student_enrollments')->cascadeOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progression_exceptions');
    }
};
