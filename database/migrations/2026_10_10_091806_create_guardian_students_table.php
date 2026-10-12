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
        Schema::create('guardian_student', function (Blueprint $t) {
            $t->id();
            $t->string('guardian_id', 12);
            $t->string('student_id', 12);
            $t->string('relationship', 30)->nullable();
            $t->boolean('is_primary')->default(false);
            $t->boolean('can_pay')->default(true);
            $t->string('status', 20)->default('active'); // active | inactive
            $t->timestamps();

            $t->unique(['guardian_id', 'student_id']);
            $t->index(['guardian_id', 'status']);
            $t->foreign('guardian_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardian_students');
    }
};
