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
        Schema::table('mpesa_transactions', function (Blueprint $t) {
            $t->string('paid_by_guardian_id', 12)->nullable()->after('user_id');
            $t->index('paid_by_guardian_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $t) {
            $t->dropIndex(['paid_by_guardian_id']);
            $t->dropColumn('paid_by_guardian_id');
        });
    }
};
