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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('gateway')->index();
            $table->string('key_type'); // 'public', 'secret', 'webhook_secret'
            $table->longText('key_value'); // Encrypted
            $table->string('environment')->default('sandbox'); // 'sandbox', 'production'
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->dateTime('last_used_at')->nullable();
            $table->string('created_by')->nullable();

            $table->index(['gateway', 'environment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
