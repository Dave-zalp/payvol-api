<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_cards', function (Blueprint $table) {
            // Sensitive card data (populated after Strowallet provisions the card)
            $table->string('card_number')->nullable()->after('reference');
            $table->string('last4', 4)->nullable()->after('card_number');
            $table->string('cvv', 10)->nullable()->after('last4');
            $table->string('expiry', 10)->nullable()->after('cvv');

            // Customer info
            $table->string('customer_email')->nullable()->after('customer_id');

            // Billing address
            $table->string('billing_country')->nullable()->after('customer_email');
            $table->string('billing_city')->nullable()->after('billing_country');
            $table->string('billing_street')->nullable()->after('billing_city');
            $table->string('billing_zip_code')->nullable()->after('billing_street');

            // Full raw details response from the fetch-card-detail call
            $table->json('card_details')->nullable()->after('response');
        });
    }

    public function down(): void
    {
        Schema::table('virtual_cards', function (Blueprint $table) {
            $table->dropColumn([
                'card_number', 'last4', 'cvv', 'expiry',
                'customer_email',
                'billing_country', 'billing_city', 'billing_street', 'billing_zip_code',
                'card_details',
            ]);
        });
    }
};
