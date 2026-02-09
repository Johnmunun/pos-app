<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shop_id')->index();
            $table->uuid('product_id')->index();
            $table->string('batch_number');
            $table->date('expiry_date');
            $table->integer('quantity');
            $table->integer('initial_quantity');
            $table->uuid('supplier_id')->nullable()->index();
            $table->uuid('purchase_order_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour performance
            $table->index(['shop_id', 'product_id']);
            $table->index(['expiry_date']);
            $table->index(['batch_number', 'shop_id']);
            
            // Contraintes
            $table->foreign('product_id')->references('id')->on('pharmacy_products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_batches');
    }
};
