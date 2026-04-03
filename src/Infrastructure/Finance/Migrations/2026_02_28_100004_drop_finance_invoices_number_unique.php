<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // `SHOW INDEX` est MySQL spécifique. Les tests utilisent SQLite (:memory:)
        // donc on saute cette migration côté sqlite.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Sécuriser la migration : ne tente de supprimer l'index que s'il existe réellement
        $indexExists = collect(
            DB::select("SHOW INDEX FROM finance_invoices WHERE Key_name = 'finance_invoices_number_unique'")
        )->isNotEmpty();

        if ($indexExists) {
            Schema::table('finance_invoices', function (Blueprint $table) {
                $table->dropUnique('finance_invoices_number_unique');
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->unique('number', 'finance_invoices_number_unique');
        });
    }
};
