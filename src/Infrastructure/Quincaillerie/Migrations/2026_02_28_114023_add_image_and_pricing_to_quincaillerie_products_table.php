<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quincaillerie_products', function (Blueprint $table) {
            // Image du produit
            $table->string('image_path', 255)->nullable()->after('description');
            $table->string('image_type', 10)->nullable()->default('upload')->after('image_path')->comment('upload ou url');
            
            // Prix normal (remplace price_amount pour compatibilité)
            $table->decimal('price_normal', 12, 2)->nullable()->after('price_amount')->comment('Prix normal');
            
            // Prix réduit et pourcentage de réduction
            $table->decimal('price_reduced', 12, 2)->nullable()->after('price_normal')->comment('Prix réduit');
            $table->decimal('price_reduction_percent', 5, 2)->nullable()->after('price_reduced')->comment('Pourcentage de réduction');
            
            // Prix non discutable
            $table->decimal('price_non_negotiable', 12, 2)->nullable()->after('price_reduction_percent')->comment('Prix non discutable');
            
            // Prix gros
            $table->decimal('price_wholesale_normal', 12, 2)->nullable()->after('price_non_negotiable')->comment('Prix gros normal');
            $table->decimal('price_wholesale_reduced', 12, 2)->nullable()->after('price_wholesale_normal')->comment('Prix gros réduit');
            
            // Trois prix non discutable supplémentaires
            $table->decimal('price_non_negotiable_1', 12, 2)->nullable()->after('price_wholesale_reduced')->comment('Prix non discutable 1');
            $table->decimal('price_non_negotiable_2', 12, 2)->nullable()->after('price_non_negotiable_1')->comment('Prix non discutable 2');
            $table->decimal('price_non_negotiable_3', 12, 2)->nullable()->after('price_non_negotiable_2')->comment('Prix non discutable 3');
        });
        
        // Migrer les données existantes : price_amount -> price_normal
        DB::statement('UPDATE quincaillerie_products SET price_normal = price_amount WHERE price_normal IS NULL');
    }

    public function down(): void
    {
        Schema::table('quincaillerie_products', function (Blueprint $table) {
            $table->dropColumn([
                'image_path',
                'image_type',
                'price_normal',
                'price_reduced',
                'price_reduction_percent',
                'price_non_negotiable',
                'price_wholesale_normal',
                'price_wholesale_reduced',
                'price_non_negotiable_1',
                'price_non_negotiable_2',
                'price_non_negotiable_3',
            ]);
        });
    }
};
