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
        Schema::create('admission_answers', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('admission_application_id', 12);
            $t->string('admission_requirement_id', 12);
            $t->text('value')->nullable();
            $t->string('file_path')->nullable();
            $t->string('file_name')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->unique(['admission_application_id', 'admission_requirement_id'], 'admission_answers_unique');
            $t->foreign('admission_application_id')->references('id')->on('admission_applications')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_answers');
    }
};
