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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->string('type'); // 'charge', 'refund', 'capture', 'verify'
            $table->string('status')->default('pending')->index();
            $table->string('gateway_transaction_id')->unique();
            $table->string('reference_id')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error')->nullable();
            $table->integer('retry_count')->default(0);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
