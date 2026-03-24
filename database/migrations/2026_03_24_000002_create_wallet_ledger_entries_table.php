<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Optional link to the platform transaction that triggered this entry
            $table->foreignUuid('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->nullOnDelete();

            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 20, 8);
            $table->enum('currency', ['USD', 'NGN', 'USDT']);

            $table->string('description');

            // Balance snapshot after this entry — enables point-in-time balance queries
            $table->decimal('running_balance', 20, 8);

            // Unique reference for this ledger entry
            $table->string('reference')->unique();

            $table->json('metadata')->nullable();

            $table->timestamps();

            // Fast balance lookup: latest entry per wallet
            $table->index(['wallet_id', 'created_at']);
            // User ledger history per currency
            $table->index(['user_id', 'currency', 'created_at']);
            // Debit/credit breakdown per wallet
            $table->index(['wallet_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger_entries');
    }
};
