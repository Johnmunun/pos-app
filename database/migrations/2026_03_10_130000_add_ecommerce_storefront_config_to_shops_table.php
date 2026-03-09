<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'ecommerce_storefront_config')) {
                $table->json('ecommerce_storefront_config')->nullable()->after('default_tax_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (Schema::hasColumn('shops', 'ecommerce_storefront_config')) {
                $table->dropColumn('ecommerce_storefront_config');
            }
        });
    }
};

