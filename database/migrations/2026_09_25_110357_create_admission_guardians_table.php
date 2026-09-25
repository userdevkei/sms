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
        Schema::create('admission_guardians', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('admission_application_id', 12)->index();
            $t->string('relationship', 30);
            $t->string('full_name', 150);
            $t->string('id_number', 30)->nullable();
            $t->string('phone', 20)->nullable();
            $t->string('email')->nullable();
            $t->string('occupation', 100)->nullable();
            $t->string('address')->nullable();
            $t->boolean('is_primary')->default(false);
            $t->boolean('is_emergency')->default(false);
            $t->timestamps();
            $t->softDeletes();

            $t->foreign('admission_application_id')->references('id')->on('admission_applications')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_guardians');
    }
};
