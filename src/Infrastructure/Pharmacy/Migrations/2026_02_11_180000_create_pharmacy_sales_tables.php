<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_sales', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('shop_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('status', 24)->index();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pharmacy_sale_lines', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('sale_id')->index();
            $table->string('product_id')->index();
            $table->integer('quantity');
            $table->decimal('unit_price_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('line_total_amount', 15, 2);
            $table->float('discount_percent')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('pharmacy_sales')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_sale_lines', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
        });

        Schema::dropIfExists('pharmacy_sale_lines');
        Schema::dropIfExists('pharmacy_sales');
    }
};

