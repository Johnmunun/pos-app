<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute depot_id aux tables Pharmacy (sans supprimer shop_id).
 * Permet de savoir quels produits, ventes, stock, etc. appartiennent à quel dépôt
 * lorsque l'utilisateur a plusieurs dépôts.
 * Colonnes nullable pour ne pas casser les données existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'pharmacy_products',
            'pharmacy_sales',
            'pharmacy_stock_movements',
            'pharmacy_categories',
            'pharmacy_batches',
            'pharmacy_product_batches',
            'pharmacy_purchase_orders',
            'pharmacy_customers',
            'pharmacy_suppliers',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            if (Schema::hasColumn($tableName, 'depot_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('depot_id')
                    ->nullable()
                    ->after('shop_id')
                    ->index()
                    ->comment('Dépôt concerné (si plusieurs dépôts)');

                $table->foreign('depot_id')
                    ->references('id')
                    ->on('depots')
                    ->onDelete('set null');
            });
        }

        // pharmacy_inventories : depot_id peut déjà exister (modèle) mais pas en base
        if (Schema::hasTable('pharmacy_inventories') && !Schema::hasColumn('pharmacy_inventories', 'depot_id')) {
            Schema::table('pharmacy_inventories', function (Blueprint $table) {
                $table->unsignedBigInteger('depot_id')
                    ->nullable()
                    ->after('shop_id')
                    ->index()
                    ->comment('Dépôt concerné');

                $table->foreign('depot_id')
                    ->references('id')
                    ->on('depots')
                    ->onDelete('set null');
            });
        }

        // pharmacy_stock_transfers : from_depot_id, to_depot_id
        if (Schema::hasTable('pharmacy_stock_transfers')) {
            if (!Schema::hasColumn('pharmacy_stock_transfers', 'from_depot_id')) {
                Schema::table('pharmacy_stock_transfers', function (Blueprint $table) {
                    $table->unsignedBigInteger('from_depot_id')
                        ->nullable()
                        ->after('from_shop_id')
                        ->index();

                    $table->foreign('from_depot_id')
                        ->references('id')
                        ->on('depots')
                        ->onDelete('set null');
                });
            }
            if (!Schema::hasColumn('pharmacy_stock_transfers', 'to_depot_id')) {
                Schema::table('pharmacy_stock_transfers', function (Blueprint $table) {
                    $table->unsignedBigInteger('to_depot_id')
                        ->nullable()
                        ->after('to_shop_id')
                        ->index();

                    $table->foreign('to_depot_id')
                        ->references('id')
                        ->on('depots')
                        ->onDelete('set null');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'pharmacy_products',
            'pharmacy_sales',
            'pharmacy_stock_movements',
            'pharmacy_categories',
            'pharmacy_batches',
            'pharmacy_product_batches',
            'pharmacy_purchase_orders',
            'pharmacy_customers',
            'pharmacy_suppliers',
            'pharmacy_inventories',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'depot_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['depot_id']);
            });
        }

        if (Schema::hasTable('pharmacy_stock_transfers')) {
            if (Schema::hasColumn('pharmacy_stock_transfers', 'from_depot_id')) {
                Schema::table('pharmacy_stock_transfers', function (Blueprint $table) {
                    $table->dropForeign(['from_depot_id']);
                });
            }
            if (Schema::hasColumn('pharmacy_stock_transfers', 'to_depot_id')) {
                Schema::table('pharmacy_stock_transfers', function (Blueprint $table) {
                    $table->dropForeign(['to_depot_id']);
                });
            }
        }
    }
};
