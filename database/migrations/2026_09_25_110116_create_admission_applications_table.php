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
        Schema::create('admission_applications', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('reference', 30)->unique();             // shown to everyone, NOT a credential
            $t->string('code_hash', 64)->nullable()->unique(); // continuation code (hashed) — the credential
            $t->string('status', 30)->default('draft')->index();
            $t->unsignedTinyInteger('current_step')->default(1);

            // who started it
            $t->string('contact_name');
            $t->string('contact_phone', 20)->nullable()->index();
            $t->string('contact_email')->nullable()->index();

            // step 3 — grade level
            $t->string('academic_year', 9)->nullable()->index();
            $t->string('grade_level_id', 12)->nullable()->index();
            $t->string('education_level_id', 12)->nullable()->index();
            $t->string('previous_school')->nullable();
            $t->string('previous_grade', 60)->nullable();
            $t->string('reason_for_leaving', 500)->nullable();
            $t->boolean('has_sibling_in_school')->default(false);
            $t->string('sibling_details')->nullable();
            $t->boolean('needs_boarding')->default(false);
            $t->boolean('needs_transport')->default(false);

            // step 1 — student
            $t->string('first_name', 80)->nullable();
            $t->string('middle_name', 80)->nullable();
            $t->string('last_name', 80)->nullable();
            $t->string('gender', 10)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('birth_certificate_no', 50)->nullable();
            $t->string('citizenship', 60)->nullable();
            $t->string('religion', 60)->nullable();
            $t->string('county', 80)->nullable();
            $t->string('sub_county', 80)->nullable();
            $t->string('ward', 80)->nullable();
            $t->string('home_address')->nullable();
            $t->string('student_email')->nullable();
            $t->text('special_needs')->nullable();
            $t->text('medical_conditions')->nullable();

            // review workflow
            $t->timestamp('submitted_at')->nullable();
            $t->string('reviewed_by', 12)->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->boolean('interview_required')->default(false);
            $t->dateTime('interview_at')->nullable();
            $t->string('interview_venue')->nullable();
            $t->text('interview_notes')->nullable();
            $t->text('interview_result_note')->nullable();

            // migration to a student
            $t->string('migrated_user_id', 12)->nullable()->index();
            $t->timestamp('migrated_at')->nullable();
            $t->string('migrated_by', 12)->nullable();

            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
    }
};
