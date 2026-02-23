<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            if (!Schema::hasColumn('pharmacy_products', 'unit')) {
                $table->string('unit', 50)->nullable()->after('stock');
            }
            if (!Schema::hasColumn('pharmacy_products', 'minimum_stock')) {
                $table->integer('minimum_stock')->nullable()->after('unit');
            }
            if (!Schema::hasColumn('pharmacy_products', 'cost_amount')) {
                $table->decimal('cost_amount', 10, 2)->nullable()->after('minimum_stock');
            }
            if (!Schema::hasColumn('pharmacy_products', 'manufacturer')) {
                $table->string('manufacturer', 255)->nullable()->after('cost_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->dropColumn(['unit', 'minimum_stock', 'cost_amount', 'manufacturer']);
        });
    }
};
