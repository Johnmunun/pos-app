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
            if (!Schema::hasColumn('gc_products', 'extra_images')) {
                $table->json('extra_images')->nullable()->after('image_type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gc_products')) {
            return;
        }

        Schema::table('gc_products', function (Blueprint $table) {
            if (Schema::hasColumn('gc_products', 'extra_images')) {
                $table->dropColumn('extra_images');
            }
        });
    }
};

