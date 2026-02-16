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
        Schema::create('pharmacy_product_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shop_id');
            $table->uuid('product_id');
            $table->string('batch_number', 50);
            $table->integer('quantity')->default(0);
            $table->date('expiration_date');
            $table->uuid('purchase_order_id')->nullable();
            $table->uuid('purchase_order_line_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('shop_id');
            $table->index('product_id');
            $table->index('expiration_date');
            $table->index('is_active');
            $table->index(['product_id', 'is_active']);
            $table->index(['shop_id', 'expiration_date']);
            
            // Unique constraint: one batch number per product
            $table->unique(['product_id', 'batch_number'], 'unique_product_batch');

            // Foreign keys
            $table->foreign('product_id')
                ->references('id')
                ->on('pharmacy_products')
                ->onDelete('cascade');

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('pharmacy_purchase_orders')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_product_batches');
    }
};
