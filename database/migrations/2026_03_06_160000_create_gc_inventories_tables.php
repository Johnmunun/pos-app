<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gc_inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('reference', 50)->unique();
            $table->enum('status', ['draft', 'in_progress', 'validated', 'cancelled'])->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'created_at']);
        });

        Schema::create('gc_inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_id')->index();
            $table->uuid('product_id')->index();
            $table->decimal('system_quantity', 12, 4)->default(0);
            $table->decimal('counted_quantity', 12, 4)->nullable();
            $table->decimal('difference', 12, 4)->default(0);
            $table->timestamps();

            $table->foreign('inventory_id')
                ->references('id')
                ->on('gc_inventories')
                ->onDelete('cascade');

            $table->unique(['inventory_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gc_inventory_items');
        Schema::dropIfExists('gc_inventories');
    }
};
