<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_invoices_table
 * 
 * Table pour gérer les factures
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            return;
        }

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Vente associée
            $table->unsignedBigInteger('sale_id')
                ->unique()
                ->index()
                ->comment('Vente associée');
            
            // Numéro de facture unique
            $table->string('invoice_number', 100)
                ->unique()
                ->index()
                ->comment('Numéro de facture unique');
            
            // Client
            $table->unsignedBigInteger('customer_id')
                ->nullable()
                ->index()
                ->comment('Client facturé');
            
            // Date de facturation
            $table->date('invoice_date')
                ->index()
                ->comment('Date de facturation');
            
            // Date d'échéance
            $table->date('due_date')
                ->nullable()
                ->comment('Date d\'échéance');
            
            // Montant total
            $table->decimal('total_amount', 10, 2)
                ->comment('Montant total TTC');
            
            // Montant payé
            $table->decimal('paid_amount', 10, 2)
                ->default(0.00)
                ->comment('Montant payé');
            
            // Montant restant
            $table->decimal('remaining_amount', 10, 2)
                ->comment('Montant restant à payer');
            
            // Statut
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])
                ->default('draft')
                ->index()
                ->comment('Statut de la facture');
            
            // Notes
            $table->text('notes')
                ->nullable()
                ->comment('Notes sur la facture');
            
            $table->timestamps();
            
            // Index
            $table->index(['tenant_id', 'status', 'invoice_date']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'due_date']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('sale_id')
                ->references('id')
                ->on('sales')
                ->onDelete('cascade');
            
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
