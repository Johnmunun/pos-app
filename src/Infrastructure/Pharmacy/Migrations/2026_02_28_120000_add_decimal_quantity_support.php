<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support des quantités décimales (ex. demi-plaquette, 0.5).
 * Fréquent en Afrique : vente au détail (demi-plaquette, quart de boîte).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->decimal('stock', 12, 4)->default(0)->change();
        });

        Schema::table('pharmacy_sale_lines', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
        });

        if (Schema::hasTable('pharmacy_batches')) {
            Schema::table('pharmacy_batches', function (Blueprint $table) {
                $table->decimal('quantity', 12, 4)->default(0)->change();
                if (Schema::hasColumn('pharmacy_batches', 'initial_quantity')) {
                    $table->decimal('initial_quantity', 12, 4)->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('pharmacy_stock_movements')) {
            Schema::table('pharmacy_stock_movements', function (Blueprint $table) {
                $table->decimal('quantity', 12, 4)->change();
            });
        }

        if (Schema::hasTable('pharmacy_product_batches')) {
            Schema::table('pharmacy_product_batches', function (Blueprint $table) {
                $table->decimal('quantity', 12, 4)->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->change();
        });

        Schema::table('pharmacy_sale_lines', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        if (Schema::hasTable('pharmacy_batches')) {
            Schema::table('pharmacy_batches', function (Blueprint $table) {
                $table->integer('quantity')->default(0)->change();
                if (Schema::hasColumn('pharmacy_batches', 'initial_quantity')) {
                    $table->integer('initial_quantity')->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('pharmacy_stock_movements')) {
            Schema::table('pharmacy_stock_movements', function (Blueprint $table) {
                $table->integer('quantity')->change();
            });
        }

        if (Schema::hasTable('pharmacy_product_batches')) {
            Schema::table('pharmacy_product_batches', function (Blueprint $table) {
                $table->integer('quantity')->default(0)->change();
            });
        }
    }
};
