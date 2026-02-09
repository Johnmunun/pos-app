<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajout des champs devise pour traçabilité multi-devise
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Vérification défensive avant ajout
            if (!Schema::hasColumn('sales', 'currency')) {
                $table->string('currency', 3)
                    ->nullable()
                    ->after('total')
                    ->index()
                    ->comment('Code devise de la vente (USD, XAF, EUR, etc.)');
            }
            
            if (!Schema::hasColumn('sales', 'exchange_rate_snapshot')) {
                $table->json('exchange_rate_snapshot')
                    ->nullable()
                    ->after('currency')
                    ->comment('Snapshot JSON des taux de change utilisés au moment de la vente');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'exchange_rate_snapshot')) {
                $table->dropColumn('exchange_rate_snapshot');
            }
            
            if (Schema::hasColumn('sales', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};
