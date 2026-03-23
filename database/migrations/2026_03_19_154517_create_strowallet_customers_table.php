<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strowallet_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('customer_id')->unique();

            $table->string('customer_email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();

            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('line1')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('house_number')->nullable();

            $table->string('id_number')->nullable();
            $table->string('id_type')->nullable();

            $table->string('id_image')->nullable();
            $table->string('user_photo')->nullable();

            $table->date('date_of_birth')->nullable();

            // 🔥 FULL RAW RESPONSE (IMPORTANT)
            $table->json('response')->nullable();

            $table->timestamps();
            $table->index(['user_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strowallet_customers');
    }
};
