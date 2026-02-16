<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_supplier_product_prices', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('supplier_id')->index();
            $table->string('product_id')->index();
            $table->decimal('normal_price', 15, 2);
            $table->decimal('agreed_price', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->date('effective_from');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index unique pour éviter les doublons actifs
            $table->unique(['supplier_id', 'product_id', 'is_active'], 'unique_supplier_product_active');
            
            // Foreign keys
            $table->foreign('supplier_id')
                ->references('id')
                ->on('pharmacy_suppliers')
                ->onDelete('cascade');
            
            $table->foreign('product_id')
                ->references('id')
                ->on('pharmacy_products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_supplier_product_prices');
    }
};
