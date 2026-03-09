<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ecommerce_reviews')) {
            return;
        }
        try {
            Schema::table('ecommerce_reviews', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        } catch (\Throwable $e) {
            // FK may not exist or have different name
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('ecommerce_reviews')) {
            return;
        }
        Schema::table('ecommerce_reviews', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('ecommerce_products')
                ->onDelete('cascade');
        });
    }
};
