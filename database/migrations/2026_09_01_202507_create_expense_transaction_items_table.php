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
        Schema::create('expense_transaction_items', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('expense_transaction_id', 12);
            $table->foreign('expense_transaction_id')->references('id')->on('expense_transactions')->cascadeOnDelete();
            $table->string('expense_category_id', 12);
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->restrictOnDelete();
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_transaction_items');
    }
};
