<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ecommerce_order_items')) {
            return;
        }

        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_order_items', 'is_digital')) {
                $table->boolean('is_digital')->default(false)->after('product_image_url');
            }
            if (!Schema::hasColumn('ecommerce_order_items', 'download_token')) {
                $table->string('download_token', 64)->nullable()->unique()->after('is_digital');
            }
            if (!Schema::hasColumn('ecommerce_order_items', 'download_expires_at')) {
                $table->timestamp('download_expires_at')->nullable()->after('download_token');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ecommerce_order_items')) {
            return;
        }

        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            foreach (['download_expires_at', 'download_token', 'is_digital'] as $col) {
                if (Schema::hasColumn('ecommerce_order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
