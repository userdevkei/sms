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
        Schema::create('payment_schedules', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('title');
            $t->date('period');                         // first day of the month
            $t->enum('status', ['draft', 'approved', 'paid'])->default('draft')->index();
            $t->decimal('total_gross', 14, 2)->default(0);
            $t->decimal('total_paye', 14, 2)->default(0);
            $t->decimal('total_nssf', 14, 2)->default(0);
            $t->decimal('total_shif', 14, 2)->default(0);
            $t->decimal('total_housing_levy', 14, 2)->default(0);
            $t->decimal('total_net', 14, 2)->default(0);
            $t->decimal('total_employer_cost', 14, 2)->default(0);
            $t->string('prepared_by', 12)->nullable();
            $t->string('approved_by', 12)->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->date('paid_on')->nullable();
            $t->string('payment_reference')->nullable();
            $t->text('notes')->nullable();
            $t->json('rate_snapshot')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};
