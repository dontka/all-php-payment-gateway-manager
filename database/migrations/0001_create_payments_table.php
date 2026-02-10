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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('gateway')->index();
            $table->string('status')->default('pending')->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('transaction_id')->unique()->nullable();
            $table->string('customer_id')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->string('payment_method')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['gateway', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
