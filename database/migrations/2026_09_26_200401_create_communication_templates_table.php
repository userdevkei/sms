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
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('trigger_key')->nullable()->unique(); // e.g. 'admission.code_issued'; null = manual-use-only template
            $table->string('name');
            $table->json('channels'); // subset of ['sms','email','whatsapp']
            $table->string('subject')->nullable();      // email only
            $table->text('sms_body')->nullable();
            $table->text('email_body')->nullable();
            $table->text('whatsapp_body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 26)->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_templates');
    }
};
