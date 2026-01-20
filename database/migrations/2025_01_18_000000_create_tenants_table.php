<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_tenants_table
 *
 * Crée la table 'tenants' qui stocke les données des commerçants/boutiques.
 *
 * Cette table est la fondation du multi-tenancy.
 * TOUS les autres enregistrements sont associés à un tenant via tenant_id.
 *
 * Champs:
 * - id: Clé primaire
 * - code: Code unique lisible (ex: "SHOP001") - Index unique
 * - name: Nom commercial
 * - email: Email de contact - Index unique
 * - is_active: État d'activation (true par défaut)
 * - created_at, updated_at: Timestamps
 *
 * Indexes:
 * - code (unique): Pour les recherches rapides par code
 * - email (unique): Chaque email doit être unique
 * - is_active: Pour les requêtes de listing
 */
return new class extends Migration
{
    /**
     * Exécuter la migration (créer la table)
     */
    public function up(): void
    {
        if (Schema::hasTable('tenants')) {
            return; // Table already exists
        }
        
        Schema::create('tenants', function (Blueprint $table) {
            // Identifiant unique
            $table->id();

            // Code unique du tenant (3-10 caractères)
            // Index unique pour recherches rapides par code
            $table->string('code', 10)->unique()
                ->comment('Code unique du tenant (ex: SHOP001)');

            // Nom commercial du tenant
            $table->string('name', 255)
                ->comment('Nom commercial/public du tenant');

            // Email de contact
            $table->string('email', 254)->unique()
                ->comment('Email de contact du tenant');

            // État d'activation
            // true = actif et visible dans le système
            // false = suspendu/inactif
            $table->boolean('is_active')
                ->default(true)
                ->index()
                ->comment('État d\'activation du tenant (true=actif, false=inactif)');

            // Timestamps automatiques
            // created_at: Date de création du tenant
            // updated_at: Date de dernière modification
            $table->timestamps();
        });
    }

    /**
     * Annuler la migration (supprimer la table)
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
