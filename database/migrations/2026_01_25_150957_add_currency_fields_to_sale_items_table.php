<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajout des champs devise pour traçabilité et conversion multi-devise
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Vérification défensive avant ajout
            if (!Schema::hasColumn('sale_items', 'currency')) {
                $table->string('currency', 3)
                    ->nullable()
                    ->after('unit_price')
                    ->comment('Code devise originale du produit au moment de la vente');
            }
            
            if (!Schema::hasColumn('sale_items', 'converted_price')) {
                $table->decimal('converted_price', 10, 2)
                    ->nullable()
                    ->after('currency')
                    ->comment('Prix converti dans la devise de la vente (si différent)');
            }
            
            if (!Schema::hasColumn('sale_items', 'conversion_rate')) {
                $table->decimal('conversion_rate', 10, 4)
                    ->nullable()
                    ->after('converted_price')
                    ->comment('Taux de conversion utilisé (ex: 1 USD = 2500 XAF)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'conversion_rate')) {
                $table->dropColumn('conversion_rate');
            }
            
            if (Schema::hasColumn('sale_items', 'converted_price')) {
                $table->dropColumn('converted_price');
            }
            
            if (Schema::hasColumn('sale_items', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};
