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
        Schema::table('currencies', function (Blueprint $table) {
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id')
                ->index()
                ->comment('Tenant propriétaire de la devise');
            
            // Code ISO 4217 (3 caractères)
            $table->string('code', 3)
                ->after('tenant_id')
                ->comment('Code ISO 4217 (ex: XAF, USD, EUR)');
            
            // Nom de la devise
            $table->string('name', 255)
                ->after('code')
                ->comment('Nom de la devise');
            
            // Symbole
            $table->string('symbol', 10)
                ->after('name')
                ->comment('Symbole de la devise (ex: $, €, FCFA)');
            
            // Devise par défaut
            $table->boolean('is_default')
                ->default(false)
                ->after('symbol')
                ->index()
                ->comment('Devise par défaut pour ce tenant');
            
            // Statut actif
            $table->boolean('is_active')
                ->default(true)
                ->after('is_default')
                ->index()
                ->comment('Devise active/inactive');
            
            // Index unique pour code + tenant_id
            $table->unique(['code', 'tenant_id'], 'currencies_code_tenant_unique');
            
            // Foreign key vers tenants
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropUnique('currencies_code_tenant_unique');
            $table->dropColumn([
                'tenant_id',
                'code',
                'name',
                'symbol',
                'is_default',
                'is_active',
            ]);
        });
    }
};
