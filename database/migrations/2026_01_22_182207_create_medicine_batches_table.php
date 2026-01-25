<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_medicine_batches_table
 * 
 * Table pour gérer les lots de médicaments (traçabilité)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('medicine_batches')) {
            return;
        }

        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Médicament
            $table->unsignedBigInteger('medicine_id')
                ->index()
                ->comment('Médicament');
            
            // Numéro de lot
            $table->string('batch_number', 100)
                ->index()
                ->comment('Numéro de lot');
            
            // Date de fabrication
            $table->date('manufacturing_date')
                ->nullable()
                ->comment('Date de fabrication');
            
            // Date d'expiration
            $table->date('expiry_date')
                ->index()
                ->comment('Date d\'expiration');
            
            // Quantité dans le lot
            $table->integer('quantity')
                ->default(0)
                ->comment('Quantité dans le lot');
            
            // Quantité vendue
            $table->integer('sold_quantity')
                ->default(0)
                ->comment('Quantité vendue');
            
            // Quantité disponible
            $table->integer('available_quantity')
                ->default(0)
                ->index()
                ->comment('Quantité disponible');
            
            // Prix d'achat du lot
            $table->decimal('purchase_price', 10, 2)
                ->nullable()
                ->comment('Prix d\'achat du lot');
            
            // Statut
            $table->enum('status', ['active', 'expired', 'depleted', 'recalled'])
                ->default('active')
                ->index()
                ->comment('Statut du lot');
            
            $table->timestamps();
            
            // Index
            $table->unique(['tenant_id', 'medicine_id', 'batch_number']);
            $table->index(['tenant_id', 'expiry_date', 'status']);
            $table->index(['tenant_id', 'status']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('medicine_id')
                ->references('id')
                ->on('medicines')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_batches');
    }
};
