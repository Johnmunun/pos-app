<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_stock_transfers_tables
 * 
 * Tables pour la gestion des transferts de stock inter-magasins
 */
return new class extends Migration
{
    public function up(): void
    {
        // Table principale des transferts - créer seulement si elle n'existe pas déjà
        if (!Schema::hasTable('pharmacy_stock_transfers')) {
            Schema::create('pharmacy_stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Multi-tenant : pharmacy_id (correspond à shop_id principal ou tenant_id)
            $table->string('pharmacy_id', 36)->index();
            
            // Référence unique du transfert
            $table->string('reference', 50)->unique();
            
            // Magasin source
            $table->unsignedBigInteger('from_shop_id');
            
            // Magasin destination
            $table->unsignedBigInteger('to_shop_id');
            
            // Statut du transfert
            $table->enum('status', ['draft', 'validated', 'cancelled'])->default('draft');
            
            // Utilisateur créateur
            $table->unsignedBigInteger('created_by');
            
            // Utilisateur validateur
            $table->unsignedBigInteger('validated_by')->nullable();
            
            // Date de validation
            $table->timestamp('validated_at')->nullable();
            
            // Notes / commentaires
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['pharmacy_id', 'status']);
            $table->index(['from_shop_id', 'status']);
            $table->index(['to_shop_id', 'status']);
            $table->index('created_at');
            
            // Foreign keys
            $table->foreign('from_shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('restrict');
            
            $table->foreign('to_shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('restrict');
            
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
            
            $table->foreign('validated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            });
        }

        // Table des items de transfert - créer seulement si elle n'existe pas déjà
        if (!Schema::hasTable('pharmacy_stock_transfer_items')) {
            Schema::create('pharmacy_stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Référence au transfert parent
            $table->uuid('stock_transfer_id');
            
            // Produit transféré
            $table->uuid('product_id');
            
            // Quantité à transférer
            $table->integer('quantity')->unsigned();
            
            $table->timestamps();
            
            // Index
            $table->index('stock_transfer_id');
            $table->unique(['stock_transfer_id', 'product_id'], 'pst_items_unique');
            
            // Foreign keys
            $table->foreign('stock_transfer_id')
                ->references('id')
                ->on('pharmacy_stock_transfers')
                ->onDelete('cascade');
            
            $table->foreign('product_id')
                ->references('id')
                ->on('pharmacy_products')
                ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_transfer_items');
        Schema::dropIfExists('pharmacy_stock_transfers');
    }
};
