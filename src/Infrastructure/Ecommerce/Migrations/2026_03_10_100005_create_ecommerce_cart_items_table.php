<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecommerce_cart_items')) {
            return;
        }

        Schema::create('ecommerce_cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->uuid('customer_id')->nullable()->index(); // Si connecté
            $table->string('session_id')->nullable()->index(); // Si non connecté
            $table->uuid('product_id')->index();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->json('product_data')->nullable(); // Snapshot du produit au moment de l'ajout
            $table->timestamp('expires_at')->nullable(); // Expiration du panier
            $table->timestamps();

            $table->index(['customer_id', 'shop_id']);
            $table->index(['session_id', 'shop_id']);
            $table->index(['product_id', 'shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_cart_items');
    }
};
