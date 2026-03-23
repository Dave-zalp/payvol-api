<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('virtual_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('account_name');        // e.g., "David Opara"
            $table->string('account_number')->unique();
            $table->string('bank_name')->nullable();
            $table->string('provider_name'); // StrollWallet reference
            $table->string('provider_reference')->unique(); // StrollWallet reference
            $table->decimal('balance', 20, 2)->default(0); // Optional local balance
            $table->string('currency');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_accounts');
    }
};
