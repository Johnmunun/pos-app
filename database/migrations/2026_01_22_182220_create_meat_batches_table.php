<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_meat_batches_table
 * 
 * Table pour gérer les lots de viande (traçabilité)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meat_batches')) {
            return;
        }

        Schema::create('meat_batches', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Produit de viande
            $table->unsignedBigInteger('meat_product_id')
                ->index()
                ->comment('Produit de viande');
            
            // Numéro de lot
            $table->string('batch_number', 100)
                ->index()
                ->comment('Numéro de lot');
            
            // Date d'abattage
            $table->date('slaughter_date')
                ->nullable()
                ->comment('Date d\'abattage');
            
            // Date de réception
            $table->date('reception_date')
                ->index()
                ->comment('Date de réception');
            
            // Date d'expiration
            $table->date('expiry_date')
                ->index()
                ->comment('Date d\'expiration');
            
            // Poids total du lot
            $table->decimal('total_weight', 8, 2)
                ->default(0.00)
                ->comment('Poids total du lot (kg)');
            
            // Poids vendu
            $table->decimal('sold_weight', 8, 2)
                ->default(0.00)
                ->comment('Poids vendu (kg)');
            
            // Poids disponible
            $table->decimal('available_weight', 8, 2)
                ->default(0.00)
                ->index()
                ->comment('Poids disponible (kg)');
            
            // Prix d'achat du lot
            $table->decimal('purchase_price', 10, 2)
                ->nullable()
                ->comment('Prix d\'achat du lot');
            
            // Statut
            $table->enum('status', ['active', 'expired', 'depleted', 'wasted'])
                ->default('active')
                ->index()
                ->comment('Statut du lot');
            
            $table->timestamps();
            
            // Index
            $table->unique(['tenant_id', 'meat_product_id', 'batch_number']);
            $table->index(['tenant_id', 'expiry_date', 'status']);
            $table->index(['tenant_id', 'status']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('meat_product_id')
                ->references('id')
                ->on('meat_products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meat_batches');
    }
};
