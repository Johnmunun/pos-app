<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_user_depot_table
 *
 * Pivot vendeurs ↔ dépôts.
 * Un vendeur peut être affecté à un ou plusieurs dépôts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_depot')) {
            return;
        }

        Schema::create('user_depot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('depot_id')->index();

            $table->timestamps();

            $table->unique(['user_id', 'depot_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('depot_id')
                ->references('id')
                ->on('depots')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_depot');
    }
};
