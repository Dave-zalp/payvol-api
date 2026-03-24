<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Increase precision to support USDT (8 decimal places)
            $table->decimal('balance', 20, 8)->default(0)->change();
            $table->decimal('ledger_balance', 20, 8)->default(0)->change();

            $table->index('currency');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('balance', 20, 2)->default(0)->change();
            $table->decimal('ledger_balance', 20, 2)->default(0)->change();

            $table->dropIndex(['currency']);
            $table->dropIndex(['is_active']);
        });
    }
};
