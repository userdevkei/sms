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
        Schema::create('communications', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('communication_template_id', 26)->nullable();
            $table->foreign('communication_template_id')->references('id')->on('communication_templates')->nullOnDelete();

            $table->enum('channel', ['sms', 'whatsapp', 'email']);
            $table->string('subject')->nullable();
            $table->text('body');

            $table->enum('audience_type', ['manual', 'students', 'grade_level', 'fee_balance', 'custom_query', 'staff'])->default('manual');
            $table->json('audience_params')->nullable();

            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled'])->default('draft');
            $table->timestamp('send_at')->nullable();
            $table->string('recurrence_rule')->nullable(); // 'daily' | 'weekly' | 'monthly' | null
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('run_count')->default(0);

            $table->string('created_by', 26)->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'send_at']);
            $table->index(['status', 'next_run_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
