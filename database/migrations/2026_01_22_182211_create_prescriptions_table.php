<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_prescriptions_table
 * 
 * Table pour gérer les prescriptions médicales (secteur PHARMACIE)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prescriptions')) {
            return;
        }

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');
            
            // Client/Patient
            $table->unsignedBigInteger('customer_id')
                ->index()
                ->comment('Client/Patient');
            
            // Numéro de prescription
            $table->string('prescription_number', 100)
                ->unique()
                ->index()
                ->comment('Numéro de prescription unique');
            
            // Médecin prescripteur
            $table->string('prescriber_name', 255)
                ->nullable()
                ->comment('Nom du médecin prescripteur');
            
            $table->string('prescriber_license', 100)
                ->nullable()
                ->comment('Numéro de licence du médecin');
            
            // Date de prescription
            $table->date('prescription_date')
                ->index()
                ->comment('Date de prescription');
            
            // Date d'expiration de la prescription
            $table->date('expiry_date')
                ->nullable()
                ->comment('Date d\'expiration de la prescription');
            
            // Statut
            $table->enum('status', ['pending', 'filled', 'partially_filled', 'expired', 'cancelled'])
                ->default('pending')
                ->index()
                ->comment('Statut de la prescription');
            
            // Notes
            $table->text('notes')
                ->nullable()
                ->comment('Notes sur la prescription');
            
            // Image/Scan de la prescription
            $table->string('image', 500)
                ->nullable()
                ->comment('URL de l\'image/scan de la prescription');
            
            $table->timestamps();
            
            // Index
            $table->index(['tenant_id', 'customer_id', 'status']);
            $table->index(['tenant_id', 'prescription_date']);
            $table->index(['tenant_id', 'expiry_date']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
