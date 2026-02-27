<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quincaillerie_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('shop_id')->index();
            $table->unsignedBigInteger('depot_id')->nullable()->index();
            $table->string('code', 50)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_amount', 12, 2);
            $table->string('price_currency', 3)->default('USD');
            $table->decimal('stock', 12, 4)->default(0);
            $table->string('type_unite', 20)->default('UNITE');
            $table->integer('quantite_par_unite')->default(1);
            $table->boolean('est_divisible')->default(true);
            $table->decimal('minimum_stock', 12, 4)->default(0);
            $table->uuid('category_id')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->unique(['code', 'shop_id']);
            $table->foreign('category_id')->references('id')->on('quincaillerie_categories')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quincaillerie_products');
    }
};
