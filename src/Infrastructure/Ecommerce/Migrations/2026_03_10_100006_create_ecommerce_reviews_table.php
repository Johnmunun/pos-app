<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecommerce_reviews')) {
            return;
        }

        Schema::create('ecommerce_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->uuid('product_id')->index();
            $table->uuid('customer_id')->nullable()->index();
            $table->uuid('order_id')->nullable()->index(); // Pour vérifier si l'achat a été fait
            $table->string('customer_name'); // Même si customer_id est null
            $table->string('customer_email')->nullable();
            $table->integer('rating')->default(5); // 1-5 étoiles
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->json('images')->nullable(); // Photos du client
            $table->integer('helpful_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')
                ->references('id')
                ->on('ecommerce_products')
                ->onDelete('cascade');

            $table->index(['product_id', 'is_approved']);
            $table->index(['shop_id', 'is_approved']);
            $table->index(['customer_id', 'product_id']); // Un client = une review par produit
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_reviews');
    }
};
