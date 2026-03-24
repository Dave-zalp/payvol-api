<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM(
            'card_creation',
            'card_fund',
            'card_withdrawal',
            'wallet_credit',
            'wallet_debit',
            'deposit',
            'withdrawal'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM(
            'card_fund',
            'card_withdrawal',
            'wallet_credit',
            'wallet_debit',
            'deposit',
            'withdrawal'
        ) NOT NULL");
    }
};
