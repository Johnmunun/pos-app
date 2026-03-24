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
                $currencyColumn = $table->string('currency', 3)
                    ->nullable()
                    ->comment('Code devise (ex: USD, EUR, CDF)');
                if (Schema::hasColumn('products', 'selling_price')) {
                    $currencyColumn->after('selling_price');
                }
            }
            
            // Ajouter image_type si elle n'existe pas
            if (!Schema::hasColumn('products', 'image_type')) {
                $imageTypeColumn = $table->enum('image_type', ['upload', 'url'])
                    ->default('url')
                    ->comment('Type d\'image: upload (fichier) ou url (lien externe)');
                if (Schema::hasColumn('products', 'image')) {
                    $imageTypeColumn->after('image');
                }
            }
            
            // Ajouter les champs pharmacy si nécessaire
            if (!Schema::hasColumn('products', 'manufacturer')) {
                $manufacturerColumn = $table->string('manufacturer', 255)
                    ->nullable()
                    ->comment('Fabricant (pharmacie)');
                if (Schema::hasColumn('products', 'description')) {
                    $manufacturerColumn->after('description');
                }
            }
            
            if (!Schema::hasColumn('products', 'prescription_required')) {
                $prescriptionColumn = $table->boolean('prescription_required')
                    ->default(false)
                    ->comment('Prescription requise (pharmacie)');
                if (Schema::hasColumn('products', 'manufacturer')) {
                    $prescriptionColumn->after('manufacturer');
                }
            }
            
            if (!Schema::hasColumn('products', 'stock_alert_level')) {
                $stockAlertColumn = $table->integer('stock_alert_level')
                    ->default(0)
                    ->comment('Niveau d\'alerte de stock (pharmacie)');
                if (Schema::hasColumn('products', 'prescription_required')) {
                    $stockAlertColumn->after('prescription_required');
                }
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
