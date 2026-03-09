<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ecommerce_products')) {
            return;
        }

        Schema::table('ecommerce_products', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_products', 'product_type')) {
                $table->string('product_type', 32)->default('physical')->after('status');
            }
            if (!Schema::hasColumn('ecommerce_products', 'download_url')) {
                $table->string('download_url', 2048)->nullable()->after('product_type');
            }
            if (!Schema::hasColumn('ecommerce_products', 'download_path')) {
                $table->string('download_path', 512)->nullable()->after('download_url');
            }
            if (!Schema::hasColumn('ecommerce_products', 'requires_shipping')) {
                $table->boolean('requires_shipping')->default(true)->after('download_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ecommerce_products')) {
            return;
        }

        Schema::table('ecommerce_products', function (Blueprint $table) {
            foreach (['requires_shipping', 'download_path', 'download_url', 'product_type'] as $col) {
                if (Schema::hasColumn('ecommerce_products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
