<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gc_products')) {
            return;
        }

        Schema::table('gc_products', function (Blueprint $table) {
            if (!Schema::hasColumn('gc_products', 'download_url')) {
                $table->string('download_url', 2048)->nullable()->after('product_type');
            }
            if (!Schema::hasColumn('gc_products', 'download_path')) {
                $table->string('download_path', 512)->nullable()->after('download_url');
            }
            if (!Schema::hasColumn('gc_products', 'requires_shipping')) {
                $table->boolean('requires_shipping')->default(true)->after('download_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gc_products')) {
            return;
        }

        Schema::table('gc_products', function (Blueprint $table) {
            foreach (['requires_shipping', 'download_path', 'download_url'] as $col) {
                if (Schema::hasColumn('gc_products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
