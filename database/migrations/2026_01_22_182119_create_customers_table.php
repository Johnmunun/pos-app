<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_customers_table
 * 
 * Table pour gérer les clients
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            return;
        }

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire du client');
            
            // Code client unique
            $table->string('code', 50)
                ->index()
                ->comment('Code client unique');
            
            // Informations personnelles
            $table->string('first_name', 100)
                ->nullable()
                ->comment('Prénom');
            
            $table->string('last_name', 100)
                ->nullable()
                ->comment('Nom de famille');
            
            $table->string('full_name', 255)
                ->comment('Nom complet');
            
            // Contact
            $table->string('email', 255)
                ->nullable()
                ->index()
                ->comment('Email');
            
            $table->string('phone', 50)
                ->nullable()
                ->index()
                ->comment('Téléphone');
            
            // Adresse
            $table->string('address', 500)
                ->nullable()
                ->comment('Adresse');
            
            $table->string('city', 100)
                ->nullable()
                ->comment('Ville');
            
            $table->string('postal_code', 20)
                ->nullable()
                ->comment('Code postal');
            
            $table->string('country', 100)
                ->nullable()
                ->default('CM')
                ->comment('Pays');
            
            // Date de naissance
            $table->date('date_of_birth')
                ->nullable()
                ->comment('Date de naissance');
            
            // Genre
            $table->enum('gender', ['male', 'female', 'other'])
                ->nullable()
                ->comment('Genre');
            
            // Informations commerciales
            $table->decimal('credit_limit', 10, 2)
                ->default(0.00)
                ->comment('Limite de crédit');
            
            $table->decimal('total_spent', 10, 2)
                ->default(0.00)
                ->comment('Total dépensé');
            
            $table->integer('total_orders')
                ->default(0)
                ->comment('Nombre total de commandes');
            
            // Notes
            $table->text('notes')
                ->nullable()
                ->comment('Notes sur le client');
            
            // Statut
            $table->boolean('is_active')
                ->default(true)
                ->index()
                ->comment('Client actif/inactif');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'phone']);
            
            // Foreign key
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
