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
        Schema::create('admission_events', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('admission_application_id', 12)->index();
            $t->string('type', 40);
            $t->string('message');
            $t->boolean('is_public')->default(true);   // visible to the applicant on the status page
            $t->string('user_id', 12)->nullable();      // staff member, when applicable
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
        Schema::dropIfExists('admission_events');
    }
};
