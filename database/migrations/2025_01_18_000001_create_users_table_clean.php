<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_users_table
 *
 * Crée ou enrichit la table 'users' qui stocke les utilisateurs de l'application.
 *
 * Types d'utilisateurs:
 * - ROOT: Propriétaire de l'application (tenant_id = NULL)
 * - TENANT_ADMIN: Admin d'un tenant
 * - MERCHANT, SELLER, STAFF: Utilisateurs standards
 *
 * Champs importants:
 * - type: Type d'utilisateur (ROOT, TENANT_ADMIN, etc.)
 * - tenant_id: NULL pour ROOT, sinon l'ID du tenant
 * - password: Hash bcrypt du mot de passe
 * - is_active: Contrôle d'accès
 * - last_login_at: Audit
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ajouter les colonnes manquantes à la table users existante
        Schema::table('users', function (Blueprint $table) {
            // Ajouter les colonnes manquantes si elles n'existent pas
            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->comment('Tenant associé (NULL pour ROOT)');
            }
            
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 100)
                    ->nullable()
                    ->after('email')
                    ->comment('Prénom');
            }
            
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 100)
                    ->nullable()
                    ->after('first_name')
                    ->comment('Nom de famille');
            }
            
            if (!Schema::hasColumn('users', 'type')) {
                $table->enum('type', ['ROOT', 'TENANT_ADMIN', 'MERCHANT', 'SELLER', 'STAFF'])
                    ->default('MERCHANT')
                    ->after('last_name')
                    ->comment('Type d\'utilisateur');
            }
            
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')
                    ->default(true)
                    ->after('type')
                    ->comment('Utilisateur actif/inactif');
            }
            
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')
                    ->nullable()
                    ->after('email_verified_at')
                    ->comment('Dernière connexion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['tenant_id', 'first_name', 'last_name', 'type', 'is_active', 'last_login_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
