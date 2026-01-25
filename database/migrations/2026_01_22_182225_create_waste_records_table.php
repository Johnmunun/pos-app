<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_waste_records_table
 * 
 * Table pour enregistrer les pertes/gaspillages (secteur BOUCHERIE)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('waste_records')) {
            return;
        }

        Schema::create('waste_records', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Produit de viande
            $table->unsignedBigInteger('meat_product_id')
                ->index()
                ->comment('Produit de viande concerné');
            
            // Lot (optionnel)
            $table->unsignedBigInteger('meat_batch_id')
                ->nullable()
                ->index()
                ->comment('Lot concerné');
            
            // Quantité/Poids gaspillé
            $table->decimal('waste_quantity', 8, 2)
                ->comment('Quantité/Poids gaspillé');
            
            // Unité
            $table->string('unit', 50)
                ->default('kg')
                ->comment('Unité (kg, piece, etc.)');
            
            // Raison du gaspillage
            $table->enum('reason', ['expired', 'damaged', 'spoiled', 'overstock', 'other'])
                ->index()
                ->comment('Raison du gaspillage');
            
            // Description
            $table->text('description')
                ->nullable()
                ->comment('Description du gaspillage');
            
            // Coût estimé
            $table->decimal('estimated_cost', 10, 2)
                ->nullable()
                ->comment('Coût estimé du gaspillage');
            
            // Utilisateur qui a enregistré
            $table->unsignedBigInteger('recorded_by')
                ->index()
                ->comment('Utilisateur qui a enregistré le gaspillage');
            
            // Date du gaspillage
            $table->date('waste_date')
                ->index()
                ->comment('Date du gaspillage');
            
            $table->timestamps();
            
            // Index
            $table->index(['tenant_id', 'meat_product_id', 'waste_date']);
            $table->index(['tenant_id', 'reason', 'waste_date']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('meat_product_id')
                ->references('id')
                ->on('meat_products')
                ->onDelete('cascade');
            
            $table->foreign('meat_batch_id')
                ->references('id')
                ->on('meat_batches')
                ->onDelete('set null');
            
            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_records');
    }
};
