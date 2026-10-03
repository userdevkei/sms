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
        Schema::create('payment_schedule_line_items', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('payment_schedule_line_id', 12)->index();
            $t->enum('kind', ['allowance', 'reimbursement', 'deduction']);
            $t->string('name');
            $t->decimal('amount', 12, 2);
            $t->timestamps();

            $t->foreign('payment_schedule_line_id')->references('id')->on('payment_schedule_lines')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_schedule_line_items');
    }
};
