<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_sale_items_table
 * 
 * Table pour les lignes de vente (produits vendus)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_items')) {
            return;
        }

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            
            // Vente
            $table->unsignedBigInteger('sale_id')
                ->index()
                ->comment('Vente parente');
            
            // Produit
            $table->unsignedBigInteger('product_id')
                ->index()
                ->comment('Produit vendu');
            
            // Variante (optionnel)
            $table->unsignedBigInteger('product_variant_id')
                ->nullable()
                ->index()
                ->comment('Variante du produit');
            
            // Informations du produit au moment de la vente (snapshot)
            $table->string('product_name', 255)
                ->comment('Nom du produit au moment de la vente');
            
            $table->string('product_sku', 100)
                ->comment('SKU du produit au moment de la vente');
            
            // Quantité
            $table->integer('quantity')
                ->comment('Quantité vendue');
            
            // Prix unitaire
            $table->decimal('unit_price', 10, 2)
                ->comment('Prix unitaire');
            
            // Taux de TVA
            $table->decimal('tax_rate', 5, 2)
                ->default(0.00)
                ->comment('Taux de TVA (%)');
            
            // Remise
            $table->decimal('discount_amount', 10, 2)
                ->default(0.00)
                ->comment('Montant de la remise');
            
            // Sous-total HT
            $table->decimal('subtotal', 10, 2)
                ->comment('Sous-total HT (quantity * unit_price - discount)');
            
            // Montant TVA
            $table->decimal('tax_amount', 10, 2)
                ->comment('Montant de la TVA');
            
            // Total TTC
            $table->decimal('total', 10, 2)
                ->comment('Total TTC');
            
            // Notes
            $table->text('notes')
                ->nullable()
                ->comment('Notes sur la ligne');
            
            $table->timestamps();
            
            // Index
            $table->index(['sale_id', 'product_id']);
            
            // Foreign keys
            $table->foreign('sale_id')
                ->references('id')
                ->on('sales')
                ->onDelete('cascade');
            
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict');
            
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
