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

            // Drop old columns
            $table->dropColumn([
                'selfie_image',
                'nin_front',
                'nin_back',
            ]);

            // Add new Cloudinary fields
            $table->string('selfie_image_url')->nullable()->after('nin_number');
            $table->string('selfie_image_public_id')->nullable()->after('selfie_image_url');

            $table->string('nin_front_url')->nullable()->after('selfie_image_public_id');
            $table->string('nin_front_public_id')->nullable()->after('nin_front_url');

            $table->string('nin_back_url')->nullable()->after('nin_front_public_id');
            $table->string('nin_back_public_id')->nullable()->after('nin_back_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {

            // Remove new fields
            $table->dropColumn([
                'selfie_image_url',
                'selfie_image_public_id',
                'nin_front_url',
                'nin_front_public_id',
                'nin_back_url',
                'nin_back_public_id',
            ]);

            // Restore old fields
            $table->string('selfie_image')->nullable();
            $table->string('nin_front')->nullable();
            $table->string('nin_back')->nullable();
        });
    }
};
