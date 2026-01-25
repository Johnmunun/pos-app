<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_products_table
 * 
 * Table principale pour les produits
 * Support multi-secteurs (pharmacie, supermarché, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire du produit');
            
            // Shop (optionnel, si le produit est spécifique à un shop)
            $table->unsignedBigInteger('shop_id')
                ->nullable()
                ->index()
                ->comment('Shop spécifique (NULL si disponible dans tous les shops)');
            
            // Catégorie
            $table->unsignedBigInteger('category_id')
                ->nullable()
                ->index()
                ->comment('Catégorie du produit');
            
            // SKU (Stock Keeping Unit) - Code unique
            $table->string('sku', 100)
                ->index()
                ->comment('Code SKU unique du produit');
            
            // Nom du produit
            $table->string('name', 255)
                ->comment('Nom du produit');
            
            // Description
            $table->text('description')
                ->nullable()
                ->comment('Description du produit');
            
            // Prix d'achat
            $table->decimal('purchase_price', 10, 2)
                ->default(0.00)
                ->comment('Prix d\'achat');
            
            // Prix de vente
            $table->decimal('selling_price', 10, 2)
                ->default(0.00)
                ->comment('Prix de vente');
            
            // Taux de TVA
            $table->decimal('tax_rate', 5, 2)
                ->default(0.00)
                ->comment('Taux de TVA (%)');
            
            // Unité de mesure
            $table->string('unit', 50)
                ->default('piece')
                ->comment('Unité de mesure (piece, kg, liter, etc.)');
            
            // Image principale
            $table->string('image', 500)
                ->nullable()
                ->comment('URL de l\'image principale');
            
            // Images supplémentaires (JSON)
            $table->json('images')
                ->nullable()
                ->comment('Tableau d\'URLs d\'images supplémentaires');
            
            // Code-barres
            $table->string('barcode', 100)
                ->nullable()
                ->index()
                ->comment('Code-barres (EAN, UPC, etc.)');
            
            // Poids
            $table->decimal('weight', 8, 2)
                ->nullable()
                ->comment('Poids en kg');
            
            // Dimensions
            $table->decimal('length', 8, 2)
                ->nullable()
                ->comment('Longueur en cm');
            
            $table->decimal('width', 8, 2)
                ->nullable()
                ->comment('Largeur en cm');
            
            $table->decimal('height', 8, 2)
                ->nullable()
                ->comment('Hauteur en cm');
            
            // Stock minimum
            $table->integer('min_stock_level')
                ->default(0)
                ->comment('Stock minimum (alerte)');
            
            // Statut
            $table->boolean('is_active')
                ->default(true)
                ->index()
                ->comment('Produit actif/inactif');
            
            $table->boolean('is_tracked')
                ->default(true)
                ->comment('Le stock est-il suivi ?');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'category_id', 'is_active']);
            $table->index(['tenant_id', 'shop_id']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('set null');
            
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
