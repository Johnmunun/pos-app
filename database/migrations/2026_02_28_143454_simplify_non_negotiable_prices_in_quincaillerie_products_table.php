<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quincaillerie_products', function (Blueprint $table) {
            // Supprimer les anciens champs multiples
            $table->dropColumn([
                'price_non_negotiable_1',
                'price_non_negotiable_2',
                'price_non_negotiable_3',
                'price_non_negotiable_wholesale_1',
                'price_non_negotiable_wholesale_2',
                'price_non_negotiable_wholesale_3',
            ]);
            
            // Ajouter un seul champ pour prix non discutable gros
            $table->decimal('price_non_negotiable_wholesale', 12, 2)->nullable()->after('price_wholesale_reduced')->comment('Prix non discutable gros');
        });
    }

    public function down(): void
    {
        Schema::table('quincaillerie_products', function (Blueprint $table) {
            // Supprimer le nouveau champ
            $table->dropColumn('price_non_negotiable_wholesale');
            
            // Restaurer les anciens champs
            $table->decimal('price_non_negotiable_1', 12, 2)->nullable()->after('price_wholesale_reduced')->comment('Prix non discutable 1');
            $table->decimal('price_non_negotiable_2', 12, 2)->nullable()->after('price_non_negotiable_1')->comment('Prix non discutable 2');
            $table->decimal('price_non_negotiable_3', 12, 2)->nullable()->after('price_non_negotiable_2')->comment('Prix non discutable 3');
            $table->decimal('price_non_negotiable_wholesale_1', 12, 2)->nullable()->after('price_non_negotiable_3')->comment('Prix non discutable gros 1');
            $table->decimal('price_non_negotiable_wholesale_2', 12, 2)->nullable()->after('price_non_negotiable_wholesale_1')->comment('Prix non discutable gros 2');
            $table->decimal('price_non_negotiable_wholesale_3', 12, 2)->nullable()->after('price_non_negotiable_wholesale_2')->comment('Prix non discutable gros 3');
        });
    }
};
