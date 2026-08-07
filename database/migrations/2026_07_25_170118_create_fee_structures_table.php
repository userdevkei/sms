<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('grade_level_id', 12);
            $table->unsignedInteger('version')->default(1);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('created_by', 12);
            $table->string('published_by', 12)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['grade_level_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
