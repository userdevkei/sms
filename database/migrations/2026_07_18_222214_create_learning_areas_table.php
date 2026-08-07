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
        Schema::create('learning_areas', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('name', 50);
            $table->string('code', 20)->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_compulsory')->default(true);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_areas');
    }
};
