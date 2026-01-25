<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_cash_register_sessions_table
 * 
 * Table pour gérer les sessions de caisse (ouverture/fermeture)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_register_sessions')) {
            return;
        }

        Schema::create('cash_register_sessions', function (Blueprint $table) {
            $table->id();
            
            // Caisse
            $table->unsignedBigInteger('cash_register_id')
                ->index()
                ->comment('Caisse concernée');
            
            // Utilisateur qui ouvre la session
            $table->unsignedBigInteger('opened_by')
                ->index()
                ->comment('Utilisateur qui a ouvert la session');
            
            // Utilisateur qui ferme la session
            $table->unsignedBigInteger('closed_by')
                ->nullable()
                ->index()
                ->comment('Utilisateur qui a fermé la session');
            
            // Solde d'ouverture
            $table->decimal('opening_balance', 10, 2)
                ->default(0.00)
                ->comment('Solde d\'ouverture');
            
            // Solde de fermeture
            $table->decimal('closing_balance', 10, 2)
                ->nullable()
                ->comment('Solde de fermeture');
            
            // Solde attendu (calculé)
            $table->decimal('expected_balance', 10, 2)
                ->nullable()
                ->comment('Solde attendu (calculé)');
            
            // Différence
            $table->decimal('difference', 10, 2)
                ->nullable()
                ->comment('Différence entre attendu et réel');
            
            // Statut
            $table->enum('status', ['open', 'closed', 'cancelled'])
                ->default('open')
                ->index()
                ->comment('Statut de la session');
            
            // Date/heure d'ouverture
            $table->timestamp('opened_at')
                ->comment('Date/heure d\'ouverture');
            
            // Date/heure de fermeture
            $table->timestamp('closed_at')
                ->nullable()
                ->index()
                ->comment('Date/heure de fermeture');
            
            // Notes
            $table->text('notes')
                ->nullable()
                ->comment('Notes sur la session');
            
            $table->timestamps();
            
            // Index
            $table->index(['cash_register_id', 'status']);
            $table->index(['opened_by', 'opened_at']);
            
            // Foreign keys
            $table->foreign('cash_register_id')
                ->references('id')
                ->on('cash_registers')
                ->onDelete('cascade');
            
            $table->foreign('opened_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
            
            $table->foreign('closed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_sessions');
    }
};
