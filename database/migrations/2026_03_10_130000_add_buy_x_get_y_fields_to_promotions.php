<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ecommerce_promotions')) {
            return;
        }
        Schema::table('ecommerce_promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('ecommerce_promotions', 'buy_quantity')) {
                $table->integer('buy_quantity')->nullable()->after('discount_value');
            }
            if (!Schema::hasColumn('ecommerce_promotions', 'get_quantity')) {
                $table->integer('get_quantity')->nullable()->after('buy_quantity');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ecommerce_promotions')) {
            return;
        }
        Schema::table('ecommerce_promotions', function (Blueprint $table) {
            $table->dropColumn(['buy_quantity', 'get_quantity']);
        });
    }
};
