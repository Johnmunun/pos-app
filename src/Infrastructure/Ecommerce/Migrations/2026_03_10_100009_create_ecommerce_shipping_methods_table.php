<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecommerce_shipping_methods')) {
            return;
        }

        Schema::create('ecommerce_shipping_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // flat_rate, weight_based, price_based, free
            $table->decimal('base_cost', 12, 2)->default(0);
            $table->decimal('free_shipping_threshold', 12, 2)->nullable();
            $table->json('zones')->nullable(); // Zones de livraison (pays, régions)
            $table->json('weight_ranges')->nullable(); // Tranches de poids avec coûts
            $table->json('price_ranges')->nullable(); // Tranches de prix avec coûts
            $table->integer('estimated_days_min')->nullable();
            $table->integer('estimated_days_max')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_shipping_methods');
    }
};
