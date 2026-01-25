<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_promotions_table
 * 
 * Table pour gérer les promotions et remises
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('promotions')) {
            return;
        }

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Nom de la promotion
            $table->string('name', 255)
                ->comment('Nom de la promotion');
            
            // Code de promotion
            $table->string('code', 100)
                ->nullable()
                ->index()
                ->comment('Code de promotion (pour codes promo)');
            
            // Type de promotion
            $table->enum('type', ['percentage', 'fixed_amount', 'buy_x_get_y', 'free_shipping'])
                ->default('percentage')
                ->comment('Type de promotion');
            
            // Valeur de la promotion
            $table->decimal('value', 10, 2)
                ->comment('Valeur de la promotion');
            
            // Produits concernés (JSON array d'IDs ou NULL pour tous)
            $table->json('product_ids')
                ->nullable()
                ->comment('IDs des produits concernés (NULL = tous)');
            
            // Catégories concernées (JSON array d'IDs ou NULL pour toutes)
            $table->json('category_ids')
                ->nullable()
                ->comment('IDs des catégories concernées (NULL = toutes)');
            
            // Montant minimum d'achat
            $table->decimal('minimum_purchase', 10, 2)
                ->nullable()
                ->comment('Montant minimum d\'achat requis');
            
            // Date de début
            $table->timestamp('start_date')
                ->index()
                ->comment('Date de début de la promotion');
            
            // Date de fin
            $table->timestamp('end_date')
                ->nullable()
                ->index()
                ->comment('Date de fin de la promotion');
            
            // Limite d'utilisation (NULL = illimité)
            $table->integer('usage_limit')
                ->nullable()
                ->comment('Limite d\'utilisation (NULL = illimité)');
            
            // Nombre d'utilisations
            $table->integer('usage_count')
                ->default(0)
                ->comment('Nombre d\'utilisations');
            
            // Statut
            $table->boolean('is_active')
                ->default(true)
                ->index()
                ->comment('Promotion active/inactive');
            
            $table->timestamps();
            
            // Index
            $table->index(['tenant_id', 'is_active', 'start_date', 'end_date']);
            $table->index(['tenant_id', 'code']);
            
            // Foreign key
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
