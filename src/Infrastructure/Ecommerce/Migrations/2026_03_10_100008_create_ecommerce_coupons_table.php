<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecommerce_coupons')) {
            return;
        }

        Schema::create('ecommerce_coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('code')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // percentage, fixed_amount, free_shipping
            $table->decimal('discount_value', 12, 2);
            $table->decimal('minimum_purchase', 12, 2)->nullable();
            $table->integer('maximum_uses')->nullable(); // Nombre total d'utilisations
            $table->integer('maximum_uses_per_customer')->nullable();
            $table->integer('used_count')->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->json('applicable_products')->nullable();
            $table->json('applicable_categories')->nullable();
            $table->json('excluded_products')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'is_active']);
            $table->index(['code', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_coupons');
    }
};
