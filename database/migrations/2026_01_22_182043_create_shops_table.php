<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_shops_table
 * 
 * Table pour gérer les points de vente (boutiques physiques ou en ligne)
 * Un tenant peut avoir plusieurs shops
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shops')) {
            return;
        }

        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire du shop');
            
            // Nom du point de vente
            $table->string('name', 255)
                ->comment('Nom du point de vente');
            
            // Code unique du shop
            $table->string('code', 50)
                ->unique()
                ->comment('Code unique du shop');
            
            // Type de shop (physical, online, both)
            $table->enum('type', ['physical', 'online', 'both'])
                ->default('physical')
                ->comment('Type de point de vente');
            
            // Adresse
            $table->string('address', 500)
                ->nullable()
                ->comment('Adresse complète');
            
            $table->string('city', 100)
                ->nullable()
                ->comment('Ville');
            
            $table->string('postal_code', 20)
                ->nullable()
                ->comment('Code postal');
            
            $table->string('country', 100)
                ->nullable()
                ->default('CM')
                ->comment('Pays');
            
            // Coordonnées
            $table->string('phone', 50)
                ->nullable()
                ->comment('Téléphone');
            
            $table->string('email', 255)
                ->nullable()
                ->comment('Email de contact');
            
            // Devise
            $table->string('currency', 3)
                ->default('XAF')
                ->comment('Devise (XAF, EUR, USD, etc.)');
            
            // Taux de TVA par défaut
            $table->decimal('default_tax_rate', 5, 2)
                ->default(0.00)
                ->comment('Taux de TVA par défaut (%)');
            
            // Statut
            $table->boolean('is_active')
                ->default(true)
                ->index()
                ->comment('Shop actif/inactif');
            
            $table->timestamps();
            
            // Index
            $table->index(['tenant_id', 'is_active']);
            
            // Foreign key
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
