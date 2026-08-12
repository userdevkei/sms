<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('payment_number')->unique(); // e.g. RCT-2026-000456
            $table->string('invoice_id', 12)->nullable();
            $table->string('user_id', 12); // denormalized from invoice for fast querying/reporting
            $table->enum('method', ['cash', 'mpesa', 'bank']);
            $table->string('gateway')->nullable();
            $table->string('gateway_transaction_id')->nullable()->unique();
            $table->decimal('amount', 10, 2);
            $table->string('reference_number')->nullable(); // M-Pesa code, bank deposit slip number, etc.
            $table->date('paid_on');
            $table->string('received_by', 12)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('received_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
