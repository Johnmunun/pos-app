<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Crée les rôles "système" (tenant_id = null) que les propriétaires
 * peuvent assigner à leurs vendeurs sans avoir à créer de rôles.
 * ROOT exécute ce seeder une fois ; les rôles apparaissent pour tous les tenants du secteur.
 */
class DefaultSectorRolesSeeder extends Seeder
{
    public function run(): void
    {
        $this->createVendeurPharmacyRole();
        // Possibilité d'ajouter : Vendeur Boucherie, Vendeur Kiosque, etc.
    }

    private function createVendeurPharmacyRole(): void
    {
        $name = 'Vendeur Pharmacie';
        if (Role::where('name', $name)->whereNull('tenant_id')->exists()) {
            if ($this->command) {
                $this->command->info("Rôle \"{$name}\" (global) existe déjà.");
            }
            return;
        }

        $role = Role::create([
            'tenant_id' => null,
            'name' => $name,
            'description' => 'Rôle par défaut pour les vendeurs en pharmacie (créé par le système). Assignable par le propriétaire.',
            'is_active' => true,
        ]);

        $codes = [
            'module.pharmacy',
            'pharmacy.product.view',
            'pharmacy.sales.view',
            'pharmacy.sales.manage',
            'stock.view',
            'pharmacy.customer.view',
        ];

        /** @var array<int, int> $permissionIds */
        $permissionIds = Permission::where('is_old', false)
            ->whereIn('code', $codes)
            ->pluck('id')
            ->toArray();

        if ($permissionIds !== []) {
            $role->permissions()->sync($permissionIds);
        }

        if ($this->command) {
            $this->command->info("Rôle \"{$name}\" créé avec " . count($permissionIds) . " permission(s).");
        }
    }
}
