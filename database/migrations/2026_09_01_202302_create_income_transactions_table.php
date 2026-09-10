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
        Schema::create('income_transactions', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('reference')->nullable();   // Receipt No.
            $table->date('transaction_date');
            $table->string('academic_year', 9);
            $table->unsignedTinyInteger('term');
            $table->string('received_from')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0); // cached sum of items
            $table->string('recorded_by', 12);
            $table->foreign('recorded_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['academic_year', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_transactions');
    }
};
