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
        Schema::create('student_change_requests', function (Blueprint $t) {
            $t->id();
            $t->string('student_id', 12);
            $t->string('requested_by', 12);
            $t->string('field', 50);
            $t->text('old_value')->nullable();
            $t->text('new_value');
            $t->string('status', 20)->default('pending'); // pending | approved | rejected
            $t->string('reviewed_by', 12)->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('review_note')->nullable();
            $t->timestamps();

            $t->index(['status', 'created_at']);
            $t->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('requested_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_change_requests');
    }
};
