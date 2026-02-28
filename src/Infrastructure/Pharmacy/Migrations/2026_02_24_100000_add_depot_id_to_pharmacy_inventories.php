<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute depot_id à la table pharmacy_inventories.
 * Permet de savoir quels inventaires appartiennent à quel dépôt
 * lorsque l'utilisateur a plusieurs dépôts.
 * Colonne nullable pour ne pas casser les données existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pharmacy_inventories') && !Schema::hasColumn('pharmacy_inventories', 'depot_id')) {
            Schema::table('pharmacy_inventories', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        if (Schema::hasTable('pharmacy_inventories') && Schema::hasColumn('pharmacy_inventories', 'depot_id')) {
            Schema::table('pharmacy_inventories', function (Blueprint $table) {
                $table->dropForeign(['depot_id']);
                $table->dropColumn('depot_id');
            });
        }
    }
};
