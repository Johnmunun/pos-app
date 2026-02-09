<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Add missing columns required for tenant registration
            if (!Schema::hasColumn('tenants', 'address')) {
                $table->text('address')->nullable()->after('name')
                    ->comment('Adresse complète du tenant');
            }
            
            if (!Schema::hasColumn('tenants', 'phone')) {
                $table->string('phone', 50)->nullable()->after('address')
                    ->comment('Numéro de téléphone du tenant');
            }
            
            if (!Schema::hasColumn('tenants', 'business_type')) {
                $table->string('business_type', 50)->nullable()->after('phone')
                    ->comment('Type de commerce (sarl, sa, sas, etc.)');
            }
            
            if (!Schema::hasColumn('tenants', 'idnat')) {
                $table->string('idnat', 50)->nullable()->after('business_type')
                    ->comment('Numéro IDNAT (Identifiant National pour les Traders)');
            }
            
            if (!Schema::hasColumn('tenants', 'rccm')) {
                $table->string('rccm', 50)->nullable()->after('idnat')
                    ->comment('Numéro RCCM (Registre de Commerce et de Crédit Mobilier)');
            }
            
            if (!Schema::hasColumn('tenants', 'tax_id')) {
                $table->string('tax_id', 50)->nullable()->after('rccm')
                    ->comment('Numéro d\'identification fiscale');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Remove the columns added in up()
            if (Schema::hasColumn('tenants', 'address')) {
                $table->dropColumn('address');
            }
            
            if (Schema::hasColumn('tenants', 'phone')) {
                $table->dropColumn('phone');
            }
            
            if (Schema::hasColumn('tenants', 'business_type')) {
                $table->dropColumn('business_type');
            }
            
            if (Schema::hasColumn('tenants', 'idnat')) {
                $table->dropColumn('idnat');
            }
            
            if (Schema::hasColumn('tenants', 'rccm')) {
                $table->dropColumn('rccm');
            }
            
            if (Schema::hasColumn('tenants', 'tax_id')) {
                $table->dropColumn('tax_id');
            }
        });
    }
};
