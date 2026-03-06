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
            // Prix minimum (non négociable) — détail / gros (montants dans la devise du produit)
            if (!Schema::hasColumn('gc_products', 'min_sale_price_amount')) {
                $table->decimal('min_sale_price_amount', 12, 2)->nullable()->after('sale_price_currency');
            }
            if (!Schema::hasColumn('gc_products', 'min_wholesale_price_amount')) {
                $table->decimal('min_wholesale_price_amount', 12, 2)->nullable()->after('wholesale_price_amount');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gc_products')) {
            return;
        }

        Schema::table('gc_products', function (Blueprint $table) {
            if (Schema::hasColumn('gc_products', 'min_wholesale_price_amount')) {
                $table->dropColumn('min_wholesale_price_amount');
            }
            if (Schema::hasColumn('gc_products', 'min_sale_price_amount')) {
                $table->dropColumn('min_sale_price_amount');
            }
        });
    }
};

