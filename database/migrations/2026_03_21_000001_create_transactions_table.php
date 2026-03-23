<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Unique platform reference (e.g. TXN-2026-XXXXXXXX)
            $table->string('reference')->unique();

            // What kind of money movement
            $table->enum('type', [
                'card_fund',
                'card_withdrawal',
                'wallet_credit',
                'wallet_debit',
                'deposit',
                'withdrawal',
            ]);

            // Which product/channel triggered this
            $table->enum('channel', [
                'virtual_card',
                'virtual_account',
                'bank_transfer',
                'crypto',
                'manual',
            ]);

            $table->decimal('amount', 20, 2);
            $table->decimal('fee', 20, 2)->default(0);
            $table->string('currency', 10)->default('USD');

            // Wallet snapshot at time of transaction
            $table->decimal('balance_before', 20, 2)->nullable();
            $table->decimal('balance_after', 20, 2)->nullable();

            $table->enum('status', ['pending', 'success', 'failed', 'reversed'])->default('pending');

            $table->string('description')->nullable();

            // Polymorphic: link to VirtualCard, VirtualAccount, etc.
            $table->nullableUuidMorphs('transactable');

            // Raw provider response for audit
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('status');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
