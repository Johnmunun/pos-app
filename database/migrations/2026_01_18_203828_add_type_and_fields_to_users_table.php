<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ajouter les colonnes manquantes si elles n'existent pas
            if (!Schema::hasColumn('users', 'type')) {
                $table->enum('type', ['ROOT', 'TENANT_ADMIN', 'MERCHANT', 'SELLER', 'STAFF'])
                    ->default('MERCHANT')
                    ->after('email')
                    ->comment('Type d\'utilisateur');
            }
            
            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->comment('Tenant associé (NULL pour ROOT)');
            }
            
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 100)
                    ->after('name')
                    ->comment('Prénom');
            }
            
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 100)
                    ->after('first_name')
                    ->comment('Nom de famille');
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('users', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
        });
    }
};
