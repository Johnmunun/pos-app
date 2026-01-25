<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_cash_registers_table
 * 
 * Table pour gérer les caisses enregistreuses
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_registers')) {
            return;
        }

        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Shop
            $table->unsignedBigInteger('shop_id')
                ->index()
                ->comment('Shop où se trouve la caisse');
            
            // Nom de la caisse
            $table->string('name', 255)
                ->comment('Nom de la caisse');
            
            // Code unique
            $table->string('code', 50)
                ->index()
                ->comment('Code unique de la caisse');
            
            // Description
            $table->text('description')
                ->nullable()
                ->comment('Description de la caisse');
            
            // Solde initial (fonds de caisse)
            $table->decimal('initial_balance', 10, 2)
                ->default(0.00)
                ->comment('Solde initial de la caisse');
            
            // Statut
            $table->boolean('is_active')
                ->default(true)
                ->index()
                ->comment('Caisse active/inactive');
            
            $table->timestamps();
            
            // Index
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'shop_id', 'is_active']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
