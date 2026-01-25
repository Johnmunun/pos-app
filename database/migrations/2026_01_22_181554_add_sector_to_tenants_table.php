<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     * 
     * Ajoute le champ sector aux tenants pour gérer les secteurs d'activité
     * (pharmacie, kiosque, supermarché, boucherie, etc.)
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'sector')) {
                $table->string('sector', 50)
                    ->nullable()
                    ->after('slug')
                    ->index()
                    ->comment('Secteur d\'activité (pharmacy, kiosk, supermarket, butcher, etc.)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'sector')) {
                $table->dropColumn('sector');
            }
        });
    }
};
