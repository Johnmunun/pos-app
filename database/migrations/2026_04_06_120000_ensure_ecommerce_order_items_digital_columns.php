<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Répare les bases où la migration 2026_03_06_170002 est marquée « exécutée »
 * mais les colonnes n’existent pas (restauration SQL, échec partiel, autre environnement).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ecommerce_order_items')) {
            return;
        }

        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_order_items', 'is_digital')) {
                $table->boolean('is_digital')->default(false);
            }
        });

        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_order_items', 'download_token')) {
                $table->string('download_token', 64)->nullable()->unique();
            }
        });

        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_order_items', 'download_expires_at')) {
                $table->timestamp('download_expires_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ecommerce_order_items')) {
            return;
        }

        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_order_items', 'download_expires_at')) {
                $table->dropColumn('download_expires_at');
            }
        });
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_order_items', 'download_token')) {
                $table->dropColumn('download_token');
            }
        });
        Schema::table('ecommerce_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_order_items', 'is_digital')) {
                $table->dropColumn('is_digital');
            }
        });
    }
};
