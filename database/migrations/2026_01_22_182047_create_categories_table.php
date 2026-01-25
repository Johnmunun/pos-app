<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_categories_table
 * 
 * Table pour gérer les catégories de produits
 * Support de catégories hiérarchiques (parent_id)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('categories')) {
            return;
        }

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            
            // Tenant (isolation multi-tenant)
            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire de la catégorie');
            
            // Catégorie parente (pour hiérarchie)
            $table->unsignedBigInteger('parent_id')
                ->nullable()
                ->index()
                ->comment('Catégorie parente (NULL pour catégorie racine)');
            
            // Nom de la catégorie
            $table->string('name', 255)
                ->comment('Nom de la catégorie');
            
            // Slug pour URLs
            $table->string('slug', 255)
                ->index()
                ->comment('Slug pour URLs');
            
            // Description
            $table->text('description')
                ->nullable()
                ->comment('Description de la catégorie');
            
            // Image
            $table->string('image', 500)
                ->nullable()
                ->comment('URL de l\'image de la catégorie');
            
            // Ordre d'affichage
            $table->integer('sort_order')
                ->default(0)
                ->comment('Ordre d\'affichage');
            
            // Statut
            $table->boolean('is_active')
                ->default(true)
                ->index()
                ->comment('Catégorie active/inactive');
            
            $table->timestamps();
            
            // Index composite
            $table->index(['tenant_id', 'parent_id', 'is_active']);
            $table->unique(['tenant_id', 'slug']);
            
            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
            
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
