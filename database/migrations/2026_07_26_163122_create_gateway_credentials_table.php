<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_credentials', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('gateway_id');
            $table->string('key'); // e.g. 'api_key', 'consumer_secret', 'host'
            $table->text('value'); // encrypted individually — see model cast
            $table->timestamps();

            $table->foreign('gateway_id')->references('id')->on('gateways')->cascadeOnDelete();
            $table->unique(['gateway_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_credentials');
    }
};
