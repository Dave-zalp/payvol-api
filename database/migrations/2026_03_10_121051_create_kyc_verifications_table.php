<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->string('bvn_number')->nullable();
            $table->string('nin_number')->nullable();

            $table->string('selfie_image')->nullable();
            $table->string('nin_front')->nullable();
            $table->string('nin_back')->nullable();

            $table->json('nin_info')->nullable();
            $table->json('bvn_info')->nullable();
            $table->enum('bvn_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->enum('nin_status', ['pending', 'verified', 'rejected'])->default('pending');

            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');

            $table->timestamp('verified_at')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
