<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_permissions_table
 *
 * Crée la table 'permissions' qui stocke les permissions du système.
 *
 * Les permissions sont importées depuis un fichier YAML via le domain AccessControl.
 * Aucune permission n'est hardcodée.
 *
 * Format: "module.action" (ex: "sales.create", "products.delete")
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            // Code unique de la permission
            $table->string('code', 100)->unique()
                ->comment('Code unique (ex: sales.create)');

            // Description lisible
            $table->string('description', 255)->nullable()
                ->comment('Description de la permission');

            // Groupe de permission (pour affichage)
            $table->string('group', 50)->nullable()
                ->index()
                ->comment('Groupe de permission (sales, products, etc.)');

            // Marqueur: a-t-elle été générée avant (pour migrations)
            $table->boolean('is_old')
                ->default(false)
                ->index()
                ->comment('Permission ancienne (conservée, pas active)');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
