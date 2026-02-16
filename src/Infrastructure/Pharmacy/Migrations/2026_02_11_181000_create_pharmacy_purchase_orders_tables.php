<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_purchase_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('shop_id')->index();
            $table->string('supplier_id')->index();
            $table->string('status', 32)->index();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('expected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamps();
        });

        Schema::create('pharmacy_purchase_order_lines', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('purchase_order_id')->index();
            $table->string('product_id')->index();
            $table->integer('ordered_quantity');
            $table->integer('received_quantity')->default(0);
            $table->decimal('unit_cost_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('line_total_amount', 15, 2);
            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('pharmacy_purchase_orders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_purchase_order_lines', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
        });

        Schema::dropIfExists('pharmacy_purchase_order_lines');
        Schema::dropIfExists('pharmacy_purchase_orders');
    }
};

