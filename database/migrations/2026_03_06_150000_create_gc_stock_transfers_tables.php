<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gc_stock_transfers')) {
            return;
        }

        Schema::create('gc_stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('reference', 50)->unique();
            $table->unsignedBigInteger('from_shop_id');
            $table->unsignedBigInteger('to_shop_id');
            $table->enum('status', ['draft', 'validated', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['from_shop_id', 'status']);
            $table->index(['to_shop_id', 'status']);
            $table->index('created_at');

            $table->foreign('from_shop_id')->references('id')->on('shops')->onDelete('restrict');
            $table->foreign('to_shop_id')->references('id')->on('shops')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
        });

        if (Schema::hasTable('gc_stock_transfer_items')) {
            return;
        }

        Schema::create('gc_stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_transfer_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 12, 4)->unsigned();
            $table->timestamps();

            $table->index('stock_transfer_id');
            $table->unique(['stock_transfer_id', 'product_id'], 'gc_sti_unique');

            $table->foreign('stock_transfer_id')
                ->references('id')
                ->on('gc_stock_transfers')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('gc_products')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gc_stock_transfer_items');
        Schema::dropIfExists('gc_stock_transfers');
    }
};
