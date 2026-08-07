<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateways', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->enum('type', ['sms', 'payment', 'email']);
            $table->string('provider', 100); // africas_talking | custom | mpesa | bank_api | smtp
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
