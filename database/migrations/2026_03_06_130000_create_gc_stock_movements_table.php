<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gc_stock_movements')) {
            return;
        }

        Schema::create('gc_stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->uuid('product_id')->index();
            $table->enum('type', ['IN', 'OUT', 'ADJUSTMENT'])->index();
            $table->decimal('quantity', 12, 4);
            $table->string('reference', 255)->nullable()->index();
            $table->string('reference_type', 100)->nullable()->index();
            $table->uuid('reference_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['shop_id', 'created_at']);
            $table->index(['shop_id', 'product_id', 'created_at']);

            $table->foreign('product_id')->references('id')->on('gc_products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gc_stock_movements');
    }
};

