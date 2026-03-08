<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecommerce_payment_methods')) {
            return;
        }

        Schema::create('ecommerce_payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('name');
            $table->string('code')->unique()->index(); // stripe, paypal, cash_on_delivery, etc.
            $table->text('description')->nullable();
            $table->string('type'); // card, wallet, bank_transfer, cash_on_delivery, etc.
            $table->json('config')->nullable(); // Configuration spécifique (API keys, etc.)
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->decimal('fee_fixed', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'is_active']);
            $table->index(['code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_payment_methods');
    }
};
