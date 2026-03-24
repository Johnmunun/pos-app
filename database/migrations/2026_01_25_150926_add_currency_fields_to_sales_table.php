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
                $currencyColumn = $table->string('currency', 3)
                    ->nullable()
                    ->index()
                    ->comment('Code devise de la vente (USD, XAF, EUR, etc.)');
                if (Schema::hasColumn('sales', 'total')) {
                    $currencyColumn->after('total');
                }
            }
            
            if (!Schema::hasColumn('sales', 'exchange_rate_snapshot')) {
                $snapshotColumn = $table->json('exchange_rate_snapshot')
                    ->nullable()
                    ->comment('Snapshot JSON des taux de change utilisés au moment de la vente');
                if (Schema::hasColumn('sales', 'currency')) {
                    $snapshotColumn->after('currency');
                }
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
