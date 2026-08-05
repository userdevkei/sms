<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('invoice_id', 12);
            $table->enum('source_type', ['fee_structure', 'transport', 'accommodation', 'other_charge', 'exemption']);
            $table->string('source_id', 12)->nullable(); // fee_structure_item_id / student_route_stop_id / room_allocation_id / other_charge_id / exemption_id
            $table->string('description');
            $table->decimal('amount', 10, 2); // negative for exemption/discount lines
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
