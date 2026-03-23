<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->foreignUuid('virtual_card_id')->constrained()->cascadeOnDelete();

            // Nullable: only set when a platform transaction triggered this
            $table->foreignUuid('transaction_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Provider (Strowallet) identifiers
            $table->string('provider_id')->nullable(); // UUID from Strowallet
            $table->string('card_id');                 // Strowallet card_id string

            // Transaction classification
            $table->string('type');   // e.g. authorization_declined, credit, debit
            $table->string('method'); // e.g. topup, declined, purchase

            $table->string('narrative')->nullable();

            $table->decimal('amount', 20, 2);
            $table->unsignedBigInteger('cent_amount')->default(0);
            $table->string('currency', 10)->default('usd');

            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');

            $table->string('reference')->nullable();

            // When the transaction happened on the provider side
            $table->timestamp('transacted_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('virtual_card_id');
            $table->index('status');
            $table->index('type');
            $table->unique('provider_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_transactions');
    }
};
