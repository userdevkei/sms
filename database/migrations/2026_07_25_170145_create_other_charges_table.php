<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_charges', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('other_charge_type_id', 12);
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('academic_year', 9);
            $table->unsignedTinyInteger('term');
            $table->string('grade_level_id', 12)->nullable();
            $table->string('stream_id', 12)->nullable();
            $table->string('user_id', 12)->nullable();
            $table->enum('status', ['active', 'invoiced', 'cancelled'])->default('active');
            $table->string('created_by', 12);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('other_charge_type_id')->references('id')->on('other_charge_types')->cascadeOnDelete();
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->nullOnDelete();
            $table->foreign('stream_id')->references('id')->on('streams')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_charges');
    }
};
