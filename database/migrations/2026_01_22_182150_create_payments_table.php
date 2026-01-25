<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_payments_table
 * 
 * Table pour gérer les paiements
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Vente
            $table->unsignedBigInteger('sale_id')
                ->index()
                ->comment('Vente associée');
            
            // Méthode de paiement
            $table->enum('method', ['cash', 'card', 'mobile_money', 'bank_transfer', 'check', 'credit', 'other'])
                ->index()
                ->comment('Méthode de paiement');
            
            // Montant
            $table->decimal('amount', 10, 2)
                ->comment('Montant payé');
            
            // Référence du paiement (ex: numéro de chèque, référence mobile money)
            $table->string('reference', 255)
                ->nullable()
                ->index()
                ->comment('Référence du paiement');
            
            // Statut
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'cancelled'])
                ->default('pending')
                ->index()
                ->comment('Statut du paiement');
            
            // Date de paiement
            $table->timestamp('paid_at')
                ->nullable()
                ->index()
                ->comment('Date/heure du paiement');
            
            // Notes
            $table->text('notes')
                ->nullable()
                ->comment('Notes sur le paiement');
            
            $table->timestamps();
            
            // Index
            $table->index(['tenant_id', 'sale_id']);
            $table->index(['tenant_id', 'method', 'status']);
            $table->index(['tenant_id', 'paid_at']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('sale_id')
                ->references('id')
                ->on('sales')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
