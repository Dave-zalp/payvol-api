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
        Schema::table('kyc_verifications', function (Blueprint $table) {

            $table->date('date_of_birth')->nullable()->after('nin_number');
            $table->string('home_address')->nullable()->after('date_of_birth');
            $table->string('state')->nullable()->after('home_address');
            $table->string('city')->nullable()->after('state');
            $table->string('zip_code', 20)->nullable()->after('city');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {

            $table->dropColumn([
                'date_of_birth',
                'home_address',
                'state',
                'city',
                'zip_code'
            ]);

        });
    }
};
