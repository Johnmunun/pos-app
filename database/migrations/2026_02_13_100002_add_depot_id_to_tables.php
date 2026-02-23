<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_depot_id_to_tables
 *
 * Ajoute depot_id aux tables existantes de manière non destructive.
 * Les colonnes sont nullable pour préserver les données existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // stock_levels
        if (Schema::hasTable('stock_levels') && !Schema::hasColumn('stock_levels', 'depot_id')) {
            Schema::table('stock_levels', function (Blueprint $table) {
                $table->unsignedBigInteger('depot_id')
                    ->nullable()
                    ->after('shop_id')
                    ->index()
                    ->comment('Dépôt (prioritaire sur shop_id si défini)');

                $table->foreign('depot_id')
                    ->references('id')
                    ->on('depots')
                    ->onDelete('cascade');
            });
        }

        // stock_movements
        if (Schema::hasTable('stock_movements') && !Schema::hasColumn('stock_movements', 'depot_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->unsignedBigInteger('depot_id')
                    ->nullable()
                    ->after('shop_id')
                    ->index()
                    ->comment('Dépôt concerné');

                $table->foreign('depot_id')
                    ->references('id')
                    ->on('depots')
                    ->onDelete('cascade');
            });
        }

        // sales
        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'depot_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->unsignedBigInteger('depot_id')
                    ->nullable()
                    ->after('shop_id')
                    ->index()
                    ->comment('Dépôt où la vente a été effectuée');

                $table->foreign('depot_id')
                    ->references('id')
                    ->on('depots')
                    ->onDelete('set null');
            });
        }

        // shops - lien optionnel shop → dépôt principal
        if (Schema::hasTable('shops') && !Schema::hasColumn('shops', 'depot_id')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->unsignedBigInteger('depot_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->index()
                    ->comment('Dépôt associé au point de vente');

                $table->foreign('depot_id')
                    ->references('id')
                    ->on('depots')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_levels') && Schema::hasColumn('stock_levels', 'depot_id')) {
            Schema::table('stock_levels', function (Blueprint $table) {
                $table->dropForeign(['depot_id']);
            });
        }

        if (Schema::hasTable('stock_movements') && Schema::hasColumn('stock_movements', 'depot_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropForeign(['depot_id']);
            });
        }

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'depot_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropForeign(['depot_id']);
            });
        }

        if (Schema::hasTable('shops') && Schema::hasColumn('shops', 'depot_id')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->dropForeign(['depot_id']);
            });
        }
    }
};
