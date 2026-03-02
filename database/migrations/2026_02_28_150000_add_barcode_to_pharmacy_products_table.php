<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->string('barcode', 255)->nullable()->after('code')->comment('Code-barres du produit');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }
};
