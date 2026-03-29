<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pharmacy_categories')) {
            Schema::table('pharmacy_categories', function (Blueprint $table) {
                if (! Schema::hasColumn('pharmacy_categories', 'category_code')) {
                    $table->string('category_code', 64)->nullable()->after('shop_id');
                    $table->unique(['shop_id', 'category_code'], 'pharmacy_categories_shop_category_code_unique');
                }
            });
        }

        if (Schema::hasTable('quincaillerie_categories')) {
            Schema::table('quincaillerie_categories', function (Blueprint $table) {
                if (! Schema::hasColumn('quincaillerie_categories', 'category_code')) {
                    $table->string('category_code', 64)->nullable()->after('shop_id');
                    $table->unique(['shop_id', 'category_code'], 'quincaillerie_cat_shop_code_unique');
                }
            });
        }

        if (Schema::hasTable('gc_categories')) {
            Schema::table('gc_categories', function (Blueprint $table) {
                if (! Schema::hasColumn('gc_categories', 'category_code')) {
                    $table->string('category_code', 64)->nullable()->after('shop_id');
                    $table->unique(['shop_id', 'category_code'], 'gc_categories_shop_category_code_unique');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pharmacy_categories') && Schema::hasColumn('pharmacy_categories', 'category_code')) {
            Schema::table('pharmacy_categories', function (Blueprint $table) {
                $table->dropUnique('pharmacy_categories_shop_category_code_unique');
                $table->dropColumn('category_code');
            });
        }
        if (Schema::hasTable('quincaillerie_categories') && Schema::hasColumn('quincaillerie_categories', 'category_code')) {
            Schema::table('quincaillerie_categories', function (Blueprint $table) {
                $table->dropUnique('quincaillerie_cat_shop_code_unique');
                $table->dropColumn('category_code');
            });
        }
        if (Schema::hasTable('gc_categories') && Schema::hasColumn('gc_categories', 'category_code')) {
            Schema::table('gc_categories', function (Blueprint $table) {
                $table->dropUnique('gc_categories_shop_category_code_unique');
                $table->dropColumn('category_code');
            });
        }
    }
};
