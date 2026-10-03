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
        Schema::create('staff_pay_items', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('staff_payee_id', 12)->index();
            // allowance = taxable cash | reimbursement = non-taxable | pension = own contribution to registered scheme
            // insurance_premium = for 15% relief | mortgage_interest | deduction = post-tax (sacco, loan, advance)
            $t->enum('kind', ['allowance', 'reimbursement', 'pension', 'insurance_premium', 'mortgage_interest', 'deduction']);
            $t->string('name');
            $t->decimal('amount', 12, 2);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_pay_items');
    }
};
