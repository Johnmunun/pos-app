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
        Schema::table('exchange_rates', function (Blueprint $table) {
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id')
                ->index()
                ->comment('Tenant propriétaire du taux de change');
            
            // Devise source
            $table->unsignedBigInteger('from_currency_id')
                ->after('tenant_id')
                ->index()
                ->comment('ID de la devise source');
            
            // Devise cible
            $table->unsignedBigInteger('to_currency_id')
                ->after('from_currency_id')
                ->index()
                ->comment('ID de la devise cible');
            
            // Taux de change
            $table->decimal('rate', 10, 4)
                ->after('to_currency_id')
                ->comment('Taux de change');
            
            // Date effective
            $table->date('effective_date')
                ->after('rate')
                ->comment('Date à partir de laquelle le taux est effectif');
            
            // Index unique pour éviter les doublons
            $table->unique(['from_currency_id', 'to_currency_id', 'effective_date'], 'exchange_rates_unique');
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('from_currency_id')
                ->references('id')
                ->on('currencies')
                ->onDelete('cascade');
            
            $table->foreign('to_currency_id')
                ->references('id')
                ->on('currencies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['from_currency_id']);
            $table->dropForeign(['to_currency_id']);
            $table->dropUnique('exchange_rates_unique');
            $table->dropColumn([
                'tenant_id',
                'from_currency_id',
                'to_currency_id',
                'rate',
                'effective_date',
            ]);
        });
    }
};
