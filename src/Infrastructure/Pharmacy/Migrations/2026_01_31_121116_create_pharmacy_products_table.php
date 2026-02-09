<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shop_id')->index();
            $table->string('code', 12)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // MEDICINE, PARAPHARMACY, DEVICE, VACCINE, NUTRITION
            $table->string('dosage', 50)->nullable();
            $table->decimal('price_amount', 10, 2);
            $table->string('price_currency', 3)->default('USD');
            $table->integer('stock')->default(0);
            $table->uuid('category_id')->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_prescription')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour performance
            $table->index(['shop_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index(['code', 'shop_id']);
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_products');
    }
};
