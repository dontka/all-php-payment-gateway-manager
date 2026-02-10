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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('cascade');
            $table->string('gateway')->index();
            $table->string('event_type');
            $table->string('transaction_id')->nullable()->index();
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->string('status')->default('received'); // 'received', 'processed', 'failed'
            $table->text('error')->nullable();
            $table->string('signature')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->timestamps();

            $table->index(['gateway', 'event_type']);
            $table->index(['gateway', 'created_at']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
