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
        Schema::create('quincaillerie_inventories', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('shop_id')->index();
            $table->unsignedBigInteger('depot_id')->nullable()->index();
            $table->string('reference', 50)->unique();
            $table->enum('status', ['draft', 'in_progress', 'validated', 'cancelled'])->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'created_at']);
            $table->index(['depot_id', 'status']);

            $table->foreign('depot_id')
                ->references('id')
                ->on('depots')
                ->onDelete('set null');
        });

        // Table des items d'inventaire
        Schema::create('quincaillerie_inventory_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('inventory_id')->index();
            $table->string('product_id')->index();
            $table->decimal('system_quantity', 10, 2)->default(0);
            $table->decimal('counted_quantity', 10, 2)->nullable();
            $table->decimal('difference', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('inventory_id')
                ->references('id')
                ->on('quincaillerie_inventories')
                ->onDelete('cascade');

            $table->unique(['inventory_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quincaillerie_inventory_items');
        Schema::dropIfExists('quincaillerie_inventories');
    }
};
