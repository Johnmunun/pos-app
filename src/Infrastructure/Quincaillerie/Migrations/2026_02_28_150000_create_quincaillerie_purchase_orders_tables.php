<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quincaillerie_purchase_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('shop_id')->index();
            $table->unsignedBigInteger('depot_id')->nullable()->index();
            $table->string('supplier_id')->index();
            $table->string('status', 32)->index();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('expected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->foreign('depot_id')
                ->references('id')
                ->on('depots')
                ->onDelete('set null');
        });

        Schema::create('quincaillerie_purchase_order_lines', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('purchase_order_id')->index();
            $table->string('product_id')->index();
            $table->decimal('ordered_quantity', 10, 3);
            $table->decimal('received_quantity', 10, 3)->default(0);
            $table->decimal('unit_cost_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('line_total_amount', 15, 2);
            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('quincaillerie_purchase_orders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('quincaillerie_purchase_order_lines', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
        });

        Schema::dropIfExists('quincaillerie_purchase_order_lines');
        Schema::dropIfExists('quincaillerie_purchase_orders');
    }
};
