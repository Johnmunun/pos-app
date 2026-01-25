<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_product_variants_table
 * 
 * Table pour les variantes de produits (taille, couleur, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_variants')) {
            return;
        }

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            
            // Produit parent
            $table->unsignedBigInteger('product_id')
                ->index()
                ->comment('Produit parent');
            
            // Nom de la variante (ex: "Rouge - Taille L")
            $table->string('name', 255)
                ->comment('Nom de la variante');
            
            // SKU de la variante
            $table->string('sku', 100)
                ->index()
                ->comment('SKU unique de la variante');
            
            // Code-barres spécifique
            $table->string('barcode', 100)
                ->nullable()
                ->index()
                ->comment('Code-barres de la variante');
            
            // Prix de vente (peut différer du produit parent)
            $table->decimal('selling_price', 10, 2)
                ->nullable()
                ->comment('Prix de vente (NULL = utilise prix du produit parent)');
            
            // Attributs de la variante (JSON: {"color": "red", "size": "L"})
            $table->json('attributes')
                ->nullable()
                ->comment('Attributs de la variante (couleur, taille, etc.)');
            
            // Image spécifique
            $table->string('image', 500)
                ->nullable()
                ->comment('Image spécifique de la variante');
            
            // Statut
            $table->boolean('is_active')
                ->default(true)
                ->index()
                ->comment('Variante active/inactive');
            
            $table->timestamps();
            
            // Index
            $table->unique(['product_id', 'sku']);
            $table->index(['product_id', 'is_active']);
            
            // Foreign key
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
