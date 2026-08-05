<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('invoice_number')->unique(); // human-readable, e.g. INV-2026-000123
            $table->string('user_id', 12);
            $table->string('grade_level_id', 12); // snapshot at generation time — student's grade may change later
            $table->string('academic_year', 9);
            $table->unsignedTinyInteger('term');
            $table->decimal('total_amount', 10, 2)->default(0);  // sum of all line items (charges positive, exemptions negative)
            $table->decimal('amount_paid', 10, 2)->default(0);   // denormalized cache, kept in sync by PaymentController
            $table->decimal('balance', 10, 2)->default(0);       // total_amount - amount_paid, cached for fast listing/sorting
            $table->enum('status', ['unpaid', 'partially_paid', 'paid', 'cancelled'])->default('unpaid');
            $table->date('due_date')->nullable();
            $table->string('generated_by', 12);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->cascadeOnDelete();
            $table->foreign('generated_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'academic_year', 'term']); // one invoice per student per term — regenerate, don't duplicate
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
