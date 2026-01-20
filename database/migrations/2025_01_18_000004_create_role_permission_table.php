<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_role_permission_table
 *
 * Crée la table pivot 'role_permission' qui associe permissions et rôles.
 *
 * Une permission peut être assignée à plusieurs rôles.
 * Un rôle peut avoir plusieurs permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('role_id')->index();
            $table->unsignedBigInteger('permission_id')->index();

            $table->timestamps();

            // Unique: une permission ne peut être assignée deux fois à un même rôle
            $table->unique(['role_id', 'permission_id']);

            // Foreign keys
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
    }
};
