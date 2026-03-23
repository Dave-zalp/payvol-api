<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 🔐 Provider identifiers
            $table->string('card_id')->unique(); // from Strowallet
            $table->string('card_user_id')->nullable();
            $table->string('reference')->nullable();

            // 💳 Card details
            $table->string('name_on_card')->nullable();
            $table->string('card_brand')->nullable(); // visa, mastercard
            $table->string('card_type')->nullable();  // virtual, physical

            // 📊 Status
            $table->string('card_status')->default('pending'); // pending, active, blocked

            // 🔗 Link to customer
            $table->string('customer_id')->nullable();

            // 📅 Dates
            $table->date('card_created_at')->nullable();

            // 💰 Optional future use
            $table->decimal('balance', 15, 2)->default(0);

            // 🧾 Full raw response (VERY IMPORTANT)
            $table->json('response')->nullable();

            $table->timestamps();

            // ⚡ Constraints / indexes
            $table->index('user_id');
            $table->index('card_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_cards');
    }
};
