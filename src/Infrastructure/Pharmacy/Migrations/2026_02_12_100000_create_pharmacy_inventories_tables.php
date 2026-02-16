<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table des inventaires
        Schema::create('pharmacy_inventories', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('shop_id')->index();
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

        // Table des items d'inventaire
        Schema::create('pharmacy_inventory_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('inventory_id')->index();
            $table->string('product_id')->index();
            $table->integer('system_quantity')->default(0);
            $table->integer('counted_quantity')->nullable();
            $table->integer('difference')->default(0);
            $table->timestamps();

            $table->foreign('inventory_id')
                ->references('id')
                ->on('pharmacy_inventories')
                ->onDelete('cascade');

            $table->unique(['inventory_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_inventory_items');
        Schema::dropIfExists('pharmacy_inventories');
    }
};
