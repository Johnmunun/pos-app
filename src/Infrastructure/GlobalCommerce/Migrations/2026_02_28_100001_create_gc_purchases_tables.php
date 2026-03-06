<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gc_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->uuid('supplier_id')->index();
            $table->string('status', 32)->default('pending')->index();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('expected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->foreign('supplier_id')->references('id')->on('gc_suppliers')->onDelete('restrict');
        });

        Schema::create('gc_purchase_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_id')->index();
            $table->uuid('product_id')->index();
            $table->decimal('ordered_quantity', 12, 4);
            $table->decimal('received_quantity', 12, 4)->default(0);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->string('product_name');
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('gc_purchases')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('gc_products')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gc_purchase_lines');
        Schema::dropIfExists('gc_purchases');
    }
};
