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
        Schema::create('admission_requirements', function (Blueprint $t) {
            $t->string('id', 12)->primary();
            $t->string('education_level_id', 12)->index();
            $t->string('label');
            $t->string('type', 20);                       // text|textarea|number|date|select|checkbox|file
            $t->boolean('is_required')->default(true);
            $t->string('help_text')->nullable();
            $t->json('options')->nullable();              // select choices
            $t->string('allowed_extensions')->nullable(); // file: "pdf,jpg,png"
            $t->unsignedInteger('max_size_kb')->default(2048);
            $t->unsignedInteger('sort_order')->default(0);
            $t->string('status', 20)->default('active');
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_requirements');
    }
};
