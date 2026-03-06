<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gc_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('sku', 100)->index();
            $table->string('barcode', 100)->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('category_id')->index();
            $table->decimal('purchase_price_amount', 12, 2)->default(0);
            $table->string('purchase_price_currency', 3)->default('USD');
            $table->decimal('sale_price_amount', 12, 2);
            $table->string('sale_price_currency', 3)->default('USD');
            $table->decimal('stock', 12, 4)->default(0);
            $table->decimal('minimum_stock', 12, 4)->default(0);
            $table->boolean('is_weighted')->default(false);
            $table->boolean('has_expiration')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_id', 'sku']);
            $table->index(['shop_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->foreign('category_id')->references('id')->on('gc_categories')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gc_products');
    }
};
