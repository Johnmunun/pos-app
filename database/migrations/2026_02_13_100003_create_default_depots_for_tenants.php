<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_default_depots_for_tenants
 *
 * Crée un dépôt par défaut pour chaque tenant qui en a besoin.
 * Non destructif : ne modifie pas les tenants qui ont déjà des dépôts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('depots') || !Schema::hasTable('tenants')) {
            return;
        }

        $tenantsWithoutDepots = DB::table('tenants')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('depots')
                    ->whereColumn('depots.tenant_id', 'tenants.id');
            })
            ->get(['id', 'name', 'code']);

        foreach ($tenantsWithoutDepots as $tenant) {
            $code = ($tenant->code ?? 'T' . $tenant->id) . '-DEPOT-1';
            DB::table('depots')->insert([
                'tenant_id' => $tenant->id,
                'name' => ($tenant->name ?? 'Tenant') . ' - Dépôt principal',
                'code' => $code,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Optionnel : supprimer les dépôts créés par défaut
        // On ne fait rien pour éviter de casser des données
    }
};
