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
            if (!Schema::hasColumn('gc_products', 'couleur')) {
                $table->string('couleur', 100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('gc_products', 'taille')) {
                $table->string('taille', 100)->nullable()->after('couleur');
            }
            if (!Schema::hasColumn('gc_products', 'type_produit')) {
                $table->string('type_produit', 30)->nullable()->after('taille')->comment('physique|numerique');
            }
            if (!Schema::hasColumn('gc_products', 'mode_paiement')) {
                $table->string('mode_paiement', 30)->nullable()->after('type_produit')->comment('paiement_immediat|paiement_livraison');
            }
            if (!Schema::hasColumn('gc_products', 'lien_telechargement')) {
                $table->string('lien_telechargement', 2048)->nullable()->after('mode_paiement');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gc_products')) {
            return;
        }

        Schema::table('gc_products', function (Blueprint $table) {
            foreach (['lien_telechargement', 'mode_paiement', 'type_produit', 'taille', 'couleur'] as $col) {
                if (Schema::hasColumn('gc_products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
