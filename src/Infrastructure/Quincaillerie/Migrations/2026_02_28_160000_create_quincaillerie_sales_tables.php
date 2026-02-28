<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quincaillerie_sales', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('shop_id')->index();
            $table->unsignedBigInteger('depot_id')->nullable()->index();
            $table->string('customer_id')->nullable()->index();
            $table->string('status', 24)->index();
            $table->string('sale_type', 16)->default('retail');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('cash_register_id')->nullable();
            $table->unsignedBigInteger('cash_register_session_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->foreign('depot_id')
                ->references('id')
                ->on('depots')
                ->onDelete('set null');
        });

        Schema::create('quincaillerie_sale_lines', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('sale_id')->index();
            $table->string('product_id')->index();
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('line_total_amount', 15, 2);
            $table->float('discount_percent')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('quincaillerie_sales')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('quincaillerie_sale_lines', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
        });

        Schema::dropIfExists('quincaillerie_sale_lines');
        Schema::dropIfExists('quincaillerie_sales');
    }
};
