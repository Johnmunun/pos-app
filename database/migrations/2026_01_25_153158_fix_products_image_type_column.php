<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Vérifier et ajouter/modifier currency
            if (!Schema::hasColumn('products', 'currency')) {
                $table->string('currency', 3)
                    ->nullable()
                    ->after('selling_price')
                    ->comment('Code devise (ex: USD, EUR, CDF)');
            }
            
            // Vérifier et ajouter/modifier image_type
            if (Schema::hasColumn('products', 'image_type')) {
                // Si la colonne existe mais n'est pas un ENUM, on doit la modifier
                // On va la supprimer et la recréer
                DB::statement('ALTER TABLE `products` DROP COLUMN `image_type`');
            }
            
            // Créer image_type comme ENUM
            DB::statement("ALTER TABLE `products` ADD COLUMN `image_type` ENUM('upload', 'url') DEFAULT 'url' AFTER `image`");
            
            // Ajouter les champs pharmacy si nécessaire
            if (!Schema::hasColumn('products', 'manufacturer')) {
                $table->string('manufacturer', 255)
                    ->nullable()
                    ->after('description')
                    ->comment('Fabricant (pharmacie)');
            }
            
            if (!Schema::hasColumn('products', 'prescription_required')) {
                $table->boolean('prescription_required')
                    ->default(false)
                    ->after('manufacturer')
                    ->comment('Prescription requise (pharmacie)');
            }
            
            if (!Schema::hasColumn('products', 'stock_alert_level')) {
                $table->integer('stock_alert_level')
                    ->default(0)
                    ->after('prescription_required')
                    ->comment('Niveau d\'alerte de stock (pharmacie)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'currency')) {
                $table->dropColumn('currency');
            }
            if (Schema::hasColumn('products', 'image_type')) {
                $table->dropColumn('image_type');
            }
            if (Schema::hasColumn('products', 'manufacturer')) {
                $table->dropColumn('manufacturer');
            }
            if (Schema::hasColumn('products', 'prescription_required')) {
                $table->dropColumn('prescription_required');
            }
            if (Schema::hasColumn('products', 'stock_alert_level')) {
                $table->dropColumn('stock_alert_level');
            }
        });
    }
};
