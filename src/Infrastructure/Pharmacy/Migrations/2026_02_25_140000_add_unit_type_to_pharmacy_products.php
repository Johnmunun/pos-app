<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->string('type_unite', 50)->default('UNITE')->after('stock');
            $table->unsignedInteger('quantite_par_unite')->default(1)->after('type_unite');
            $table->boolean('est_divisible')->default(true)->after('quantite_par_unite');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table) {
            $table->dropColumn(['type_unite', 'quantite_par_unite', 'est_divisible']);
        });
    }
};
