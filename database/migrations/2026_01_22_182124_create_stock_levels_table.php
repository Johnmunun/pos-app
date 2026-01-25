<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_stock_levels_table
 * 
 * Table pour gérer les niveaux de stock actuels par produit/shop
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_levels')) {
            return;
        }

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Shop (optionnel, si stock par shop)
            $table->unsignedBigInteger('shop_id')
                ->nullable()
                ->index()
                ->comment('Shop spécifique (NULL si stock global)');
            
            // Produit
            $table->unsignedBigInteger('product_id')
                ->index()
                ->comment('Produit');
            
            // Variante (optionnel)
            $table->unsignedBigInteger('product_variant_id')
                ->nullable()
                ->index()
                ->comment('Variante du produit (NULL si pas de variante)');
            
            // Quantité en stock
            $table->integer('quantity')
                ->default(0)
                ->comment('Quantité en stock');
            
            // Quantité réservée (en cours de vente)
            $table->integer('reserved_quantity')
                ->default(0)
                ->comment('Quantité réservée');
            
            // Quantité disponible (quantity - reserved_quantity)
            $table->integer('available_quantity')
                ->default(0)
                ->index()
                ->comment('Quantité disponible');
            
            // Dernière mise à jour
            $table->timestamp('last_updated_at')
                ->nullable()
                ->comment('Dernière mise à jour du stock');
            
            $table->timestamps();
            
            // Index
            $table->unique(['tenant_id', 'shop_id', 'product_id', 'product_variant_id'], 'stock_unique');
            $table->index(['tenant_id', 'product_id', 'available_quantity']);
            $table->index(['tenant_id', 'shop_id']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('cascade');
            
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
