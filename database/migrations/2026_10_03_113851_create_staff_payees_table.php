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
        Schema::create('staff_payees', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('user_id', 12)->nullable()->index();
            $t->string('staff_no', 30)->nullable()->unique();
            $t->string('full_name');
            $t->string('id_number', 30)->nullable();
            $t->string('kra_pin', 20)->nullable();
            $t->string('nssf_no', 30)->nullable();
            $t->string('shif_no', 30)->nullable();
            $t->string('designation')->nullable();
            $t->string('department')->nullable();
            $t->enum('employment_type', ['permanent', 'contract', 'casual'])->default('permanent');
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->decimal('basic_salary', 12, 2)->default(0);
            $t->enum('payment_method', ['bank', 'mpesa', 'cash'])->default('bank');
            $t->string('bank_name')->nullable();
            $t->string('bank_branch')->nullable();
            $t->string('bank_account', 40)->nullable();
            $t->string('mpesa_phone', 20)->nullable();
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
        Schema::dropIfExists('staff_payees');
    }
};
