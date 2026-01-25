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
        Schema::table('products', function (Blueprint $table) {
            // Ajouter currency si elle n'existe pas
            if (!Schema::hasColumn('products', 'currency')) {
                $table->string('currency', 3)
                    ->nullable()
                    ->after('selling_price')
                    ->comment('Code devise (ex: USD, EUR, CDF)');
            }
            
            // Ajouter image_type si elle n'existe pas
            if (!Schema::hasColumn('products', 'image_type')) {
                $table->enum('image_type', ['upload', 'url'])
                    ->default('url')
                    ->after('image')
                    ->comment('Type d\'image: upload (fichier) ou url (lien externe)');
            }
            
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
            //
        });
    }
};
