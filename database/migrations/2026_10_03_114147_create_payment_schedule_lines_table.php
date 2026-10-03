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
        Schema::create('payment_schedule_lines', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('payment_schedule_id', 12)->index();
            $t->string('staff_payee_id', 12)->nullable()->index();
            $t->string('staff_no', 30)->nullable();
            $t->string('full_name');
            $t->string('kra_pin', 20)->nullable();
            $t->string('nssf_no', 30)->nullable();
            $t->string('shif_no', 30)->nullable();
            $t->string('payment_method', 10)->default('bank');
            $t->string('bank_name')->nullable();
            $t->string('bank_account', 40)->nullable();
            $t->string('mpesa_phone', 20)->nullable();
            // inputs
            $t->decimal('basic_salary', 12, 2)->default(0);
            $t->unsignedTinyInteger('days_worked')->default(0);
            $t->unsignedTinyInteger('days_in_month')->default(30);
            $t->decimal('taxable_allowances', 12, 2)->default(0);
            $t->decimal('non_taxable_allowances', 12, 2)->default(0);
            $t->decimal('pension', 12, 2)->default(0);
            $t->decimal('insurance_premium', 12, 2)->default(0);
            $t->decimal('mortgage_interest', 12, 2)->default(0);
            $t->decimal('other_deductions', 12, 2)->default(0);
            $t->decimal('one_off_allowance', 12, 2)->default(0);
            $t->decimal('one_off_deduction', 12, 2)->default(0);
            $t->decimal('one_off_reimbursement', 12, 2)->default(0);

            // outputs
            $t->decimal('gross_pay', 12, 2)->default(0);
            $t->decimal('nssf', 12, 2)->default(0);
            $t->decimal('shif', 12, 2)->default(0);
            $t->decimal('housing_levy', 12, 2)->default(0);
            $t->decimal('taxable_pay', 12, 2)->default(0);
            $t->decimal('paye', 12, 2)->default(0);
            $t->decimal('net_pay', 12, 2)->default(0);
            $t->decimal('employer_nssf', 12, 2)->default(0);
            $t->decimal('employer_housing_levy', 12, 2)->default(0);
            $t->decimal('nita', 12, 2)->default(0);
            $t->boolean('breaches_one_third')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_schedule_lines');
    }
};
