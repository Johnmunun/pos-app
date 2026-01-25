<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_activity_logs_table
 * 
 * Table pour enregistrer les activités et actions des utilisateurs
 * Utile pour l'audit et la traçabilité
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->index()
                ->comment('Tenant associé (NULL pour actions ROOT)');
            
            // Utilisateur qui a effectué l'action
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->index()
                ->comment('Utilisateur qui a effectué l\'action');
            
            // Type d'action
            $table->string('action', 100)
                ->index()
                ->comment('Type d\'action (created, updated, deleted, etc.)');
            
            // Modèle concerné
            $table->string('model_type', 255)
                ->nullable()
                ->index()
                ->comment('Type de modèle (ex: App\\Models\\Product)');
            
            // ID du modèle concerné
            $table->unsignedBigInteger('model_id')
                ->nullable()
                ->index()
                ->comment('ID du modèle concerné');
            
            // Description de l'action
            $table->text('description')
                ->nullable()
                ->comment('Description de l\'action');
            
            // Données avant modification (JSON)
            $table->json('old_values')
                ->nullable()
                ->comment('Valeurs avant modification');
            
            // Données après modification (JSON)
            $table->json('new_values')
                ->nullable()
                ->comment('Valeurs après modification');
            
            // IP de l'utilisateur
            $table->string('ip_address', 45)
                ->nullable()
                ->comment('Adresse IP de l\'utilisateur');
            
            // User agent
            $table->text('user_agent')
                ->nullable()
                ->comment('User agent du navigateur');
            
            $table->timestamps();
            
            // Index composite pour recherches rapides
            $table->index(['tenant_id', 'user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
