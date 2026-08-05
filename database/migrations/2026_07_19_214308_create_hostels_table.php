<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostels', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('name', 100);
            $table->enum('gender', ['male', 'female', 'mixed'])->default('mixed');
            $table->string('warden_id', 12)->nullable(); // -> users.id (Hostel Warden role)
            $table->decimal('default_fee_per_term', 10, 2)->nullable(); // fallback if a room doesn't set its own fee
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warden_id')->references('id')->on('users')->nullOnDelete();
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostels');
    }
};
