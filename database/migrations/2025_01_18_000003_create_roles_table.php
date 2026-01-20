<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_roles_table
 *
 * Crée la table 'roles' qui stocke les rôles.
 * Les rôles sont créés depuis l'interface admin (aucun role hardcodé).
 *
 * Un rôle contient plusieurs permissions.
 * Un utilisateur a un ou plusieurs rôles (par tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            // Tenant auquel appartient ce rôle
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->index()
                ->comment('Tenant (NULL pour rôles globaux ROOT)');

            // Nom du rôle
            $table->string('name', 100)
                ->comment('Nom du rôle');

            // Description
            $table->text('description')
                ->nullable()
                ->comment('Description du rôle');

            // État
            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();

            // Unique: un tenant ne peut avoir 2 rôles avec le même nom
            $table->unique(['tenant_id', 'name']);

            // Foreign key
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
