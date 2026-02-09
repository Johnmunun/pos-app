<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_settings')) {
            return;
        }

        Schema::create('store_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Shop (boutique)
            $table->unsignedBigInteger('shop_id')
                ->unique()
                ->index()
                ->comment('ID de la boutique');
            
            // Identité entreprise
            $table->string('company_name', 255)
                ->comment('Nom de l\'entreprise');
            
            $table->string('id_nat', 50)
                ->nullable()
                ->comment('ID NAT');
            
            $table->string('rccm', 50)
                ->nullable()
                ->comment('RCCM');
            
            $table->string('tax_number', 50)
                ->nullable()
                ->comment('Numéro fiscal');
            
            // Adresse
            $table->string('street', 500)
                ->nullable()
                ->comment('Rue');
            
            $table->string('city', 100)
                ->nullable()
                ->comment('Ville');
            
            $table->string('postal_code', 20)
                ->nullable()
                ->comment('Code postal');
            
            $table->string('country', 100)
                ->nullable()
                ->default('CM')
                ->comment('Pays');
            
            // Coordonnées
            $table->string('phone', 50)
                ->nullable()
                ->comment('Téléphone');
            
            $table->string('email', 255)
                ->nullable()
                ->comment('Email');
            
            // Logo
            $table->string('logo_path', 500)
                ->nullable()
                ->comment('Chemin du logo');
            
            // Facturation
            $table->string('currency', 3)
                ->default('XAF')
                ->comment('Devise par défaut');
            
            $table->decimal('exchange_rate', 10, 4)
                ->nullable()
                ->comment('Taux de change');
            
            $table->text('invoice_footer_text')
                ->nullable()
                ->comment('Texte footer facture');
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('cascade');
            
            // Index
            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
