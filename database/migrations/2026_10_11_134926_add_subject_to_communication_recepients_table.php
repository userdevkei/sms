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
        Schema::table('communication_recipients', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('destination');
            $table->longText('message')->nullable()->after('subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communication_recipients', function (Blueprint $table) {
            $table->dropColumn('subject');
            $table->dropColumn('message');
        });
    }
};
