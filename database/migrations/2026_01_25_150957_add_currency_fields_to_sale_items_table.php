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
                $currencyColumn = $table->string('currency', 3)
                    ->nullable()
                    ->comment('Code devise originale du produit au moment de la vente');
                if (Schema::hasColumn('sale_items', 'unit_price')) {
                    $currencyColumn->after('unit_price');
                }
            }
            
            if (!Schema::hasColumn('sale_items', 'converted_price')) {
                $convertedPriceColumn = $table->decimal('converted_price', 10, 2)
                    ->nullable()
                    ->comment('Prix converti dans la devise de la vente (si différent)');
                if (Schema::hasColumn('sale_items', 'currency')) {
                    $convertedPriceColumn->after('currency');
                }
            }
            
            if (!Schema::hasColumn('sale_items', 'conversion_rate')) {
                $conversionRateColumn = $table->decimal('conversion_rate', 10, 4)
                    ->nullable()
                    ->comment('Taux de conversion utilisé (ex: 1 USD = 2500 XAF)');
                if (Schema::hasColumn('sale_items', 'converted_price')) {
                    $conversionRateColumn->after('converted_price');
                }
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
