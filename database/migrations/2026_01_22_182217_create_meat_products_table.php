<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_meat_products_table
 * 
 * Table spécifique pour les produits de boucherie (secteur BOUCHERIE)
 * Extension de la table products avec des champs spécifiques
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meat_products')) {
            return;
        }

        Schema::create('meat_products', function (Blueprint $table) {
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
            
            // Type de viande
            $table->enum('meat_type', ['beef', 'pork', 'chicken', 'lamb', 'goat', 'fish', 'other'])
                ->index()
                ->comment('Type de viande');
            
            // Coupe
            $table->string('cut', 100)
                ->nullable()
                ->comment('Coupe de viande (ex: filet, côte, etc.)');
            
            // Origine
            $table->string('origin', 255)
                ->nullable()
                ->comment('Origine de la viande');
            
            // Date d'abattage
            $table->date('slaughter_date')
                ->nullable()
                ->comment('Date d\'abattage');
            
            // Poids moyen par unité
            $table->decimal('average_weight', 8, 2)
                ->nullable()
                ->comment('Poids moyen par unité (kg)');
            
            // Température de conservation
            $table->decimal('storage_temperature', 5, 2)
                ->nullable()
                ->comment('Température de conservation (°C)');
            
            // Date limite de consommation
            $table->date('consumption_limit_date')
                ->nullable()
                ->index()
                ->comment('Date limite de consommation');
            
            $table->timestamps();
            
            // Index
            $table->index(['tenant_id', 'meat_type']);
            $table->index(['tenant_id', 'consumption_limit_date']);
            
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
        Schema::dropIfExists('meat_products');
    }
};
