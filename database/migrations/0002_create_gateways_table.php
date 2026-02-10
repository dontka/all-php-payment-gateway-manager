<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('type'); // 'card', 'wallet', 'bank_transfer', 'crypto', 'mobile_money'
            $table->boolean('enabled')->default(false);
            $table->json('configuration')->nullable();
            $table->json('features')->nullable(); // Capabilities like 'refund', 'verify', 'webhook'
            $table->string('support_email')->nullable();
            $table->string('webhook_url')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->json('supported_countries')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('enabled');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
