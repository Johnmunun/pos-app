<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sécuriser la migration : ne tente de supprimer l'index que s'il existe réellement
        Schema::table('finance_invoices', function (Blueprint $table) {
            // Certaines bases peuvent déjà ne plus avoir cet index (migration manuelle ou ancienne version)
            if (Schema::hasColumn('finance_invoices', 'number')) {
                try {
                    $table->dropUnique('finance_invoices_number_unique');
                } catch (\Throwable $e) {
                    // On ignore silencieusement l'erreur si l'index n'existe déjà plus
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->unique('number', 'finance_invoices_number_unique');
        });
    }
};
