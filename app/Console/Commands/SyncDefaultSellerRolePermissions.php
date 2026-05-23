<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;

/**
 * Met à jour les permissions des rôles système « Vendeur * » (déjà créés en base).
 */
class SyncDefaultSellerRolePermissions extends Command
{
    protected $signature = 'sellers:sync-default-roles';

    protected $description = 'Synchronise les permissions des rôles Vendeur Pharmacie, Commerce et Hardware';

    public function handle(): int
    {
        $map = [
            'Vendeur Pharmacie' => [
                'module.pharmacy',
                'pharmacy.product.view',
                'pharmacy.sales.view',
                'pharmacy.sales.manage',
                'stock.view',
                'pharmacy.customer.view',
            ],
            'Vendeur Hardware' => [
                'module.hardware',
                'hardware.product.view',
                'hardware.sales.view',
                'hardware.sales.create',
                'hardware.sales.manage',
                'hardware.stock.view',
                'hardware.customer.view',
            ],
            'Vendeur Commerce' => [
                'module.commerce',
                'commerce.product.view',
                'commerce.sales.view',
                'commerce.sales.create',
                'commerce.sales.manage',
                'commerce.stock.view',
                'commerce.customer.view',
            ],
        ];

        foreach ($map as $roleName => $codes) {
            $role = Role::query()->where('name', $roleName)->whereNull('tenant_id')->first();
            if ($role === null) {
                $this->warn("Rôle « {$roleName} » introuvable — exécutez DefaultSectorRolesSeeder.");
                continue;
            }

            $permissionIds = Permission::query()
                ->where('is_old', false)
                ->whereIn('code', $codes)
                ->pluck('id')
                ->toArray();

            $role->permissions()->sync($permissionIds);
            $this->info("« {$roleName} » : ".count($permissionIds).' permission(s) synchronisée(s).');
        }

        return self::SUCCESS;
    }
}
