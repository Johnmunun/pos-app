<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_depots_table
 *
 * Table pour gérer les dépôts/entrepôts du tenant.
 * Un propriétaire peut avoir plusieurs dépôts.
 * L'accès aux produits et au stock se fait par dépôt sélectionné.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('depots')) {
            return;
        }

        Schema::create('depots', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')
                ->index()
                ->comment('Tenant propriétaire');

            $table->string('name', 255)
                ->comment('Nom du dépôt');

            $table->string('code', 50)
                ->comment('Code unique du dépôt');

            $table->string('address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable()->default('CM');
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depots');
    }
};
