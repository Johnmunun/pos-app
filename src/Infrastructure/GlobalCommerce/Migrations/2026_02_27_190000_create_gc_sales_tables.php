<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gc_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('status', 20)->default('completed')->index(); // draft, completed, cancelled
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('customer_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'created_at']);
        });

        Schema::create('gc_sale_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id')->index();
            $table->uuid('product_id')->index();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->string('product_name'); // denormalized
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('gc_sales')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('gc_products')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gc_sale_lines');
        Schema::dropIfExists('gc_sales');
    }
};
