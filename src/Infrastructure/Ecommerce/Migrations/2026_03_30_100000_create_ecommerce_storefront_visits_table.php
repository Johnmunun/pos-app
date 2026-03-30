<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecommerce_storefront_visits')) {
            return;
        }

        Schema::create('ecommerce_storefront_visits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id')->index();
            $table->char('country_code', 2)->nullable()->index();
            $table->string('region_name', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('path', 500)->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_storefront_visits');
    }
};
