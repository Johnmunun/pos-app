<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_medicines_table
 * 
 * Table spécifique pour les médicaments (secteur PHARMACIE)
 * Extension de la table products avec des champs spécifiques
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('medicines')) {
            return;
        }

        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Produit associé
            $table->unsignedBigInteger('product_id')
                ->unique()
                ->index()
                ->comment('Produit associé');
            
            // DCI (Dénomination Commune Internationale)
            $table->string('dci', 255)
                ->nullable()
                ->comment('Dénomination Commune Internationale');
            
            // Forme pharmaceutique
            $table->string('pharmaceutical_form', 100)
                ->nullable()
                ->comment('Forme pharmaceutique (comprimé, sirop, etc.)');
            
            // Dosage
            $table->string('dosage', 100)
                ->nullable()
                ->comment('Dosage (ex: 500mg, 10ml)');
            
            // Conditionnement
            $table->string('packaging', 100)
                ->nullable()
                ->comment('Conditionnement (ex: boîte de 20)');
            
            // Numéro d'autorisation de mise sur le marché (AMM)
            $table->string('amm_number', 100)
                ->nullable()
                ->index()
                ->comment('Numéro d\'autorisation de mise sur le marché');
            
            // Fabricant
            $table->string('manufacturer', 255)
                ->nullable()
                ->comment('Fabricant');
            
            // Prescription requise
            $table->boolean('requires_prescription')
                ->default(false)
                ->index()
                ->comment('Prescription médicale requise');
            
            // Classe thérapeutique
            $table->string('therapeutic_class', 255)
                ->nullable()
                ->comment('Classe thérapeutique');
            
            // Principe actif
            $table->string('active_ingredient', 255)
                ->nullable()
                ->comment('Principe actif');
            
            $table->timestamps();
            
            // Index
            $table->index(['tenant_id', 'requires_prescription']);
            $table->index(['tenant_id', 'amm_number']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
