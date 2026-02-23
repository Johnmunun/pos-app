<?php

namespace Src\Domains\User\Services;

use App\Models\Permission;

/**
 * Service pour gérer les permissions par module/secteur
 * 
 * Permet de filtrer dynamiquement les permissions selon le secteur d'activité du tenant
 */
class ModulePermissionService
{
    /**
     * Obtenir les permissions autorisées pour un secteur donné
     * 
     * @param string|null $sector Secteur d'activité (pharmacy, butchery, kiosk, supermarket, etc.)
     * @return array<int, string> Liste des codes de permissions autorisées
     */
    public function getAuthorizedPermissions(?string $sector): array
    {
        if (!$sector) {
            return [];
        }

        $permissions = Permission::where('is_old', false)
            ->where(function ($query) use ($sector) {
                /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\Permission> $query */
                // Permissions communes à tous les modules
                $query->where('code', 'like', 'general.%')
                      ->orWhere('code', 'like', 'dashboard.%')
                      ->orWhere('code', 'like', 'sales.%')
                      ->orWhere('code', 'like', 'stock.%')
                      ->orWhere('code', 'like', 'inventory.%')
                      ->orWhere('code', 'like', 'customers.%')
                      ->orWhere('code', 'like', 'sellers.%')
                      ->orWhere('code', 'like', 'transfer.%')
                      ->orWhere('code', 'like', 'reports.%')
                      ->orWhere('code', 'like', 'analytics.%');

                // Permissions spécifiques au secteur
                match ($sector) {
                    'pharmacy' => $query->orWhere('code', 'like', 'pharmacy.%')
                                       ->orWhere('code', '=', 'module.pharmacy'),
                    'butchery' => $query->orWhere('code', 'like', 'butchery.%')
                                       ->orWhere('code', '=', 'module.butchery'),
                    'kiosk' => $query->orWhere('code', 'like', 'kiosk.%')
                                     ->orWhere('code', '=', 'module.kiosk'),
                    'supermarket' => $query->orWhere('code', 'like', 'supermarket.%')
                                          ->orWhere('code', '=', 'module.supermarket'),
                    'hardware' => $query->orWhere('code', 'like', 'hardware.%')
                                       ->orWhere('code', '=', 'module.hardware'),
                    default => null, // Pas de permissions spécifiques pour "other"
                };
            })
            ->pluck('code')
            ->toArray();
        
        /** @var array<int, string> $permissions */
        return $permissions;
    }

    /**
     * Vérifier si une permission est autorisée pour un secteur
     * 
     * @param string $permissionCode Code de la permission
     * @param string|null $sector Secteur d'activité
     * @return bool
     */
    public function isPermissionAuthorized(string $permissionCode, ?string $sector): bool
    {
        $authorizedPermissions = $this->getAuthorizedPermissions($sector);
        return in_array($permissionCode, $authorizedPermissions);
    }

    /**
     * Filtrer les permissions d'un rôle pour ne garder que celles autorisées pour le secteur
     * 
     * @param array<int, string> $rolePermissions Permissions du rôle
     * @param string|null $sector Secteur d'activité
     * @return array<int, string> Permissions filtrées
     */
    public function filterRolePermissions(array $rolePermissions, ?string $sector): array
    {
        $authorizedPermissions = $this->getAuthorizedPermissions($sector);
        return array_intersect($rolePermissions, $authorizedPermissions);
    }
}
