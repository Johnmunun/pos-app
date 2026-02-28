<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quincaillerie_products', function (Blueprint $table) {
            // Trois prix non discutable pour vente en gros
            $table->decimal('price_non_negotiable_wholesale_1', 12, 2)->nullable()->after('price_non_negotiable_3')->comment('Prix non discutable gros 1');
            $table->decimal('price_non_negotiable_wholesale_2', 12, 2)->nullable()->after('price_non_negotiable_wholesale_1')->comment('Prix non discutable gros 2');
            $table->decimal('price_non_negotiable_wholesale_3', 12, 2)->nullable()->after('price_non_negotiable_wholesale_2')->comment('Prix non discutable gros 3');
        });
    }

    public function down(): void
    {
        Schema::table('quincaillerie_products', function (Blueprint $table) {
            $table->dropColumn([
                'price_non_negotiable_wholesale_1',
                'price_non_negotiable_wholesale_2',
                'price_non_negotiable_wholesale_3',
            ]);
        });
    }
};
