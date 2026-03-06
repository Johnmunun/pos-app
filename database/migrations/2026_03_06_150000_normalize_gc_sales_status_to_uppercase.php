<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('gc_sales')) {
            return;
        }

        // Normaliser les statuts existants en majuscules (COMPLETED, DRAFT, CANCELLED)
        DB::table('gc_sales')->whereNotNull('status')->update([
            'status' => DB::raw("UPPER(TRIM(status))"),
        ]);

        // Remplacer les valeurs vides ou nulles par COMPLETED
        DB::table('gc_sales')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update(['status' => 'COMPLETED']);
    }

    public function down(): void
    {
        // Pas de rollback - les données normalisées restent
    }
};
