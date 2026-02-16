<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vérifier si la table existe
        if (!Schema::hasTable('pharmacy_suppliers')) {
            // Créer la table avec le bon schéma
            Schema::create('pharmacy_suppliers', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->unsignedBigInteger('shop_id')->index();
                $table->string('name');
                $table->string('contact_person')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamps();
            });
            return;
        }

        // Modifier la table existante
        Schema::table('pharmacy_suppliers', function (Blueprint $table) {
            // Ajouter contact_person si non présent
            if (!Schema::hasColumn('pharmacy_suppliers', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('name');
            }
            
            // Ajouter status si non présent
            if (!Schema::hasColumn('pharmacy_suppliers', 'status')) {
                $table->string('status', 20)->default('active')->after('address');
            }
        });

        // Migrer les données de is_active vers status
        if (Schema::hasColumn('pharmacy_suppliers', 'is_active')) {
            DB::statement("UPDATE pharmacy_suppliers SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END");
            
            Schema::table('pharmacy_suppliers', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        // Changer le type de shop_id si c'est une string
        // Note: On ne peut pas facilement changer le type en MySQL, on laisse tel quel
    }

    public function down(): void
    {
        Schema::table('pharmacy_suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('pharmacy_suppliers', 'status')) {
                $table->boolean('is_active')->default(true)->after('address');
            }
        });

        if (Schema::hasColumn('pharmacy_suppliers', 'status') && Schema::hasColumn('pharmacy_suppliers', 'is_active')) {
            DB::statement("UPDATE pharmacy_suppliers SET is_active = CASE WHEN status = 'active' THEN 1 ELSE 0 END");
            
            Schema::table('pharmacy_suppliers', function (Blueprint $table) {
                $table->dropColumn(['status', 'contact_person']);
            });
        }
    }
};
