<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_stock_movements_table
 * 
 * Table pour l'historique des mouvements de stock (entrées/sorties)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Shop (optionnel)
            $table->unsignedBigInteger('shop_id')
                ->nullable()
                ->index()
                ->comment('Shop concerné');
            
            // Produit
            $table->unsignedBigInteger('product_id')
                ->index()
                ->comment('Produit');
            
            // Variante (optionnel)
            $table->unsignedBigInteger('product_variant_id')
                ->nullable()
                ->index()
                ->comment('Variante du produit');
            
            // Type de mouvement
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer', 'sale', 'return'])
                ->index()
                ->comment('Type de mouvement');
            
            // Quantité
            $table->integer('quantity')
                ->comment('Quantité (positive pour entrée, négative pour sortie)');
            
            // Quantité avant
            $table->integer('quantity_before')
                ->comment('Quantité avant le mouvement');
            
            // Quantité après
            $table->integer('quantity_after')
                ->comment('Quantité après le mouvement');
            
            // Référence (ex: numéro de facture, bon de réception)
            $table->string('reference', 255)
                ->nullable()
                ->index()
                ->comment('Référence du mouvement');
            
            // Référence ID (ex: sale_id, purchase_id)
            $table->unsignedBigInteger('reference_id')
                ->nullable()
                ->index()
                ->comment('ID de la référence');
            
            // Type de référence (ex: sale, purchase, adjustment)
            $table->string('reference_type', 100)
                ->nullable()
                ->index()
                ->comment('Type de référence');
            
            // Raison/Motif
            $table->string('reason', 255)
                ->nullable()
                ->comment('Raison du mouvement');
            
            // Notes
            $table->text('notes')
                ->nullable()
                ->comment('Notes supplémentaires');
            
            // Utilisateur qui a effectué le mouvement
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->index()
                ->comment('Utilisateur qui a effectué le mouvement');
            
            $table->timestamps();
            
            // Index
            $table->index(['tenant_id', 'product_id', 'created_at']);
            $table->index(['tenant_id', 'type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            
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
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
