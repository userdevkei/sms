<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voteheads', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('name', 100); // Tuition, Activity Fees, Remedial, Examination, etc.
            $table->string('code', 20)->nullable()->unique();
            $table->enum('category', ['tuition', 'activity', 'remedial', 'examination', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voteheads');
    }
};
