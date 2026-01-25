<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_sales_table
 * 
 * Table principale pour les ventes
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            return;
        }

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Shop
            $table->unsignedBigInteger('shop_id')
                ->nullable()
                ->index()
                ->comment('Shop où la vente a été effectuée');
            
            // Caisse (foreign key ajoutée dans une migration séparée)
            $table->unsignedBigInteger('cash_register_id')
                ->nullable()
                ->index()
                ->comment('Caisse utilisée');
            
            // Session de caisse (foreign key ajoutée dans une migration séparée)
            $table->unsignedBigInteger('cash_register_session_id')
                ->nullable()
                ->index()
                ->comment('Session de caisse');
            
            // Numéro de vente unique
            $table->string('sale_number', 100)
                ->unique()
                ->index()
                ->comment('Numéro de vente unique');
            
            // Client (optionnel)
            $table->unsignedBigInteger('customer_id')
                ->nullable()
                ->index()
                ->comment('Client (NULL pour vente anonyme)');
            
            // Vendeur
            $table->unsignedBigInteger('seller_id')
                ->nullable()
                ->index()
                ->comment('Vendeur (utilisateur)');
            
            // Statut de la vente
            $table->enum('status', ['draft', 'completed', 'cancelled', 'refunded', 'partially_refunded'])
                ->default('draft')
                ->index()
                ->comment('Statut de la vente');
            
            // Totaux
            $table->decimal('subtotal', 10, 2)
                ->default(0.00)
                ->comment('Sous-total HT');
            
            $table->decimal('tax_amount', 10, 2)
                ->default(0.00)
                ->comment('Montant de la TVA');
            
            $table->decimal('discount_amount', 10, 2)
                ->default(0.00)
                ->comment('Montant de la remise');
            
            $table->decimal('total', 10, 2)
                ->default(0.00)
                ->comment('Total TTC');
            
            // Notes
            $table->text('notes')
                ->nullable()
                ->comment('Notes sur la vente');
            
            // Date de vente
            $table->timestamp('sold_at')
                ->nullable()
                ->index()
                ->comment('Date/heure de la vente');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['tenant_id', 'status', 'sold_at']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'seller_id']);
            $table->index(['tenant_id', 'shop_id']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('set null');
            
            // Foreign keys vers cash_registers et cash_register_sessions
            // seront ajoutées dans une migration séparée après création de ces tables
            
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('set null');
            
            $table->foreign('seller_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
