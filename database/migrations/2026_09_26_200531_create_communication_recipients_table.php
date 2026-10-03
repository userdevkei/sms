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
        Schema::create('communication_recipients', function (Blueprint $table) {
            $table->string('id', 12)->primary(); // plain auto-increment — this is a per-message log row, not a business entity
            $table->string('communication_id', 26);
            $table->foreign('communication_id')->references('id')->on('communications')->cascadeOnDelete();

            $table->string('recipient_type')->nullable();
            $table->string('recipient_id', 12)->nullable();
            $table->foreign('recipient_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('destination');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('gateway_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['communication_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_recipients');
    }
};
