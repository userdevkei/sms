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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->enum('bank', ['equity', 'coop', 'kcb']);
            $table->string('transaction_ref');       // bank's own unique txn id
            $table->string('account_reference')->nullable(); // what payer entered — should be admission_number
            $table->decimal('amount', 10, 2);
            $table->string('payer_name')->nullable();
            $table->string('payer_phone')->nullable();
            $table->timestamp('paid_at');
            $table->json('raw_payload');
            $table->enum('status', ['unmatched', 'matched', 'ignored'])->default('unmatched');
            $table->string('matched_payment_id', 12)->nullable();
            $table->string('matched_by', 12)->nullable(); // null = auto-matched
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->unique(['bank', 'transaction_ref']); // idempotency
            $table->foreign('matched_payment_id')->references('id')->on('payments')->nullOnDelete();
            $table->foreign('matched_by')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
