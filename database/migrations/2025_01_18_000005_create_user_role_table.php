<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_user_role_table
 *
 * Crée la table pivot 'user_role' qui associe utilisateurs et rôles.
 *
 * Un utilisateur peut avoir plusieurs rôles.
 * Chaque utilisateur/rôle association est spécifique à un tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_role', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('role_id')->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->timestamps();

            // Unique: un utilisateur ne peut avoir qu'un rôle par tenant
            $table->unique(['user_id', 'role_id', 'tenant_id']);

            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role');
    }
};
