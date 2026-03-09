<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gc_products')) {
            return;
        }

        Schema::table('gc_products', function (Blueprint $table) {
            if (!Schema::hasColumn('gc_products', 'is_published_ecommerce')) {
                $table->boolean('is_published_ecommerce')
                    ->default(false)
                    ->after('is_active');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gc_products')) {
            return;
        }

        Schema::table('gc_products', function (Blueprint $table) {
            if (Schema::hasColumn('gc_products', 'is_published_ecommerce')) {
                $table->dropColumn('is_published_ecommerce');
            }
        });
    }
};

