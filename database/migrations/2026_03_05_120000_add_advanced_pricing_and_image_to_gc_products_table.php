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
            if (!Schema::hasColumn('gc_products', 'image_path')) {
                $table->string('image_path')->nullable()->after('description');
            }
            if (!Schema::hasColumn('gc_products', 'image_type')) {
                $table->string('image_type', 50)->nullable()->after('image_path');
            }
            if (!Schema::hasColumn('gc_products', 'wholesale_price_amount')) {
                $table->decimal('wholesale_price_amount', 12, 2)->nullable()->after('sale_price_currency');
            }
            if (!Schema::hasColumn('gc_products', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->nullable()->after('wholesale_price_amount');
            }
            if (!Schema::hasColumn('gc_products', 'price_non_negotiable')) {
                $table->boolean('price_non_negotiable')->default(false)->after('discount_percent');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gc_products')) {
            return;
        }

        Schema::table('gc_products', function (Blueprint $table) {
            if (Schema::hasColumn('gc_products', 'price_non_negotiable')) {
                $table->dropColumn('price_non_negotiable');
            }
            if (Schema::hasColumn('gc_products', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
            if (Schema::hasColumn('gc_products', 'wholesale_price_amount')) {
                $table->dropColumn('wholesale_price_amount');
            }
            if (Schema::hasColumn('gc_products', 'image_type')) {
                $table->dropColumn('image_type');
            }
            if (Schema::hasColumn('gc_products', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};

