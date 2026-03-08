<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->index();
            $table->uuid('product_id')->index();
            $table->string('product_name'); // denormalized for history
            $table->string('product_sku')->nullable();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->string('product_image_url')->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('ecommerce_orders')
                ->onDelete('cascade');

            $table->index(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_order_items');
    }
};
