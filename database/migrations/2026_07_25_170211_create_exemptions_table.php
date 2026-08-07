<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exemptions', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('user_id', 12);
            $table->string('votehead_id', 12)->nullable(); // null = applies to the WHOLE invoice, not one line
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 10, 2); // percentage (0-100) or a fixed KES amount, depending on type
            $table->string('academic_year', 9);
            $table->unsignedTinyInteger('term');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('requested_by', 12);
            $table->string('approved_by', 12)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('votehead_id')->references('id')->on('voteheads')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exemptions');
    }
};
