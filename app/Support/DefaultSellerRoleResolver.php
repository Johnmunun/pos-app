<?php

namespace App\Support;

use App\Models\Role;
use App\Models\Tenant;

final class DefaultSellerRoleResolver
{
    public static function roleIdForTenant(string|int $tenantId): ?int
    {
        $tenant = Tenant::find($tenantId);
        if ($tenant === null) {
            return null;
        }

        $roleName = match ($tenant->sector) {
            'pharmacy' => 'Vendeur Pharmacie',
            'hardware' => 'Vendeur Hardware',
            'commerce', 'global_commerce', 'kiosk', 'supermarket', 'butchery', 'other' => 'Vendeur Commerce',
            default => null,
        };

        if ($roleName === null) {
            return null;
        }

        $role = Role::query()
            ->where('name', $roleName)
            ->whereNull('tenant_id')
            ->where('is_active', true)
            ->first();

        return $role?->id;
    }

    /**
     * @return list<int>
     */
    public static function roleIdsForTenant(string|int $tenantId): array
    {
        $id = self::roleIdForTenant($tenantId);

        return $id !== null ? [$id] : [];
    }
}
