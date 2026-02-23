<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            if (!Schema::hasColumn('pharmacy_products', 'wholesale_price_amount')) {
                $table->decimal('wholesale_price_amount', 10, 2)->nullable()->after('price_amount');
            }
            if (!Schema::hasColumn('pharmacy_products', 'wholesale_min_quantity')) {
                $table->integer('wholesale_min_quantity')->nullable()->after('wholesale_price_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->dropColumn(['wholesale_price_amount', 'wholesale_min_quantity']);
        });
    }
};
