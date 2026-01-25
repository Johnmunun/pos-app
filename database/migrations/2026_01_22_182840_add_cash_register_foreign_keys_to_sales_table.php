<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: add_cash_register_foreign_keys_to_sales_table
 * 
 * Ajoute les foreign keys vers cash_registers et cash_register_sessions
 * dans la table sales (après création de ces tables)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Vérifier que les tables existent avant d'ajouter les foreign keys
        if (!Schema::hasTable('sales') || !Schema::hasTable('cash_registers') || !Schema::hasTable('cash_register_sessions')) {
            return;
        }
        
        // Vérifier si les colonnes existent
        if (!Schema::hasColumn('sales', 'cash_register_id') || !Schema::hasColumn('sales', 'cash_register_session_id')) {
            return;
        }
        
        // Utiliser des requêtes SQL directes pour vérifier et ajouter les foreign keys
        $connection = DB::connection();
        $databaseName = $connection->getDatabaseName();
        
        // Vérifier si la foreign key cash_register_id existe déjà
        $fkCashRegisterExists = DB::selectOne("
            SELECT COUNT(*) as count
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = 'sales'
            AND COLUMN_NAME = 'cash_register_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$databaseName]);
        
        if ($fkCashRegisterExists && $fkCashRegisterExists->count == 0) {
            DB::statement("
                ALTER TABLE sales
                ADD CONSTRAINT sales_cash_register_id_foreign
                FOREIGN KEY (cash_register_id) REFERENCES cash_registers(id)
                ON DELETE SET NULL
            ");
        }
        
        // Vérifier si la foreign key cash_register_session_id existe déjà
        $fkSessionExists = DB::selectOne("
            SELECT COUNT(*) as count
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = 'sales'
            AND COLUMN_NAME = 'cash_register_session_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$databaseName]);
        
        if ($fkSessionExists && $fkSessionExists->count == 0) {
            DB::statement("
                ALTER TABLE sales
                ADD CONSTRAINT sales_cash_register_session_id_foreign
                FOREIGN KEY (cash_register_session_id) REFERENCES cash_register_sessions(id)
                ON DELETE SET NULL
            ");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sales')) {
            return;
        }
        
        // Supprimer les foreign keys si elles existent
        try {
            DB::statement("ALTER TABLE sales DROP FOREIGN KEY sales_cash_register_id_foreign");
        } catch (\Exception $e) {
            // Foreign key n'existe pas, on ignore
        }
        
        try {
            DB::statement("ALTER TABLE sales DROP FOREIGN KEY sales_cash_register_session_id_foreign");
        } catch (\Exception $e) {
            // Foreign key n'existe pas, on ignore
        }
    }
};
