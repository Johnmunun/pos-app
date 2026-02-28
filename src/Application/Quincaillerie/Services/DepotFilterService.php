<?php

namespace Src\Application\Quincaillerie\Services;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service de filtrage par dépôt pour le module Quincaillerie
 * 
 * Gère le filtrage des données selon :
 * - Les permissions de l'utilisateur (view_all_warehouse vs view_warehouse)
 * - Le dépôt sélectionné dans la session
 * - Les dépôts assignés à l'utilisateur
 * 
 * Règles :
 * - Si l'utilisateur a view_all_warehouse : voit tous les dépôts
 * - Si l'utilisateur a view_warehouse : voit uniquement ses dépôts assignés
 * - Les produits sans depot_id (null) sont considérés comme "dépôt central" et visibles par tous
 */
class DepotFilterService
{
    private const PERMISSION_VIEW_ALL = 'hardware.warehouse.view_all';
    private const PERMISSION_VIEW = 'hardware.warehouse.view';

    /**
     * Applique le filtrage par dépôt sur une requête Eloquent
     * 
     * @param Builder $query
     * @param Request $request
     * @param string $depotColumn Nom de la colonne depot_id (par défaut 'depot_id')
     * @return Builder
     */
    public function applyDepotFilter(Builder $query, Request $request, string $depotColumn = 'depot_id'): Builder
    {
        $user = $request->user();
        if (!$user) {
            return $query;
        }

        $permissions = $user->permissionCodes();
        $currentDepotId = $request->session()->get('current_depot_id');

        // Si l'utilisateur a la permission view_all_warehouse, il voit tout
        if ($this->hasPermission($permissions, self::PERMISSION_VIEW_ALL)) {
            // Si un dépôt est sélectionné, filtrer par ce dépôt + dépôt central (null)
            if ($currentDepotId) {
                return $query->where(function ($q) use ($depotColumn, $currentDepotId) {
                    $q->where($depotColumn, $currentDepotId)
                      ->orWhereNull($depotColumn); // Dépôt central
                });
            }
            // Sinon, voir tous les produits (tous les dépôts + dépôt central)
            return $query;
        }

        // Si l'utilisateur a seulement view_warehouse, il voit uniquement ses dépôts assignés
        if ($this->hasPermission($permissions, self::PERMISSION_VIEW)) {
            $userDepotIds = $this->getUserDepotIds($user);
            
            if (empty($userDepotIds)) {
                // Aucun dépôt assigné : ne voir que le dépôt central
                return $query->whereNull($depotColumn);
            }

            // Si un dépôt est sélectionné ET que l'utilisateur y a accès
            if ($currentDepotId && in_array((int) $currentDepotId, $userDepotIds, true)) {
                return $query->where(function ($q) use ($depotColumn, $currentDepotId) {
                    $q->where($depotColumn, $currentDepotId)
                      ->orWhereNull($depotColumn); // Dépôt central toujours visible
                });
            }

            // Voir tous les dépôts assignés + dépôt central
            return $query->where(function ($q) use ($depotColumn, $userDepotIds) {
                $q->whereIn($depotColumn, $userDepotIds)
                  ->orWhereNull($depotColumn); // Dépôt central
            });
        }

        // Pas de permission warehouse : ne voir que le dépôt central
        return $query->whereNull($depotColumn);
    }

    /**
     * Vérifie si l'utilisateur peut voir tous les dépôts
     */
    public function canViewAllDepots($user): bool
    {
        if (!$user) {
            return false;
        }
        $permissions = $user->permissionCodes();
        return $this->hasPermission($permissions, self::PERMISSION_VIEW_ALL);
    }

    /**
     * Vérifie si l'utilisateur peut voir au moins un dépôt
     */
    public function canViewAnyDepot($user): bool
    {
        if (!$user) {
            return false;
        }
        $permissions = $user->permissionCodes();
        return $this->hasPermission($permissions, self::PERMISSION_VIEW_ALL) 
            || $this->hasPermission($permissions, self::PERMISSION_VIEW);
    }

    /**
     * Récupère les IDs des dépôts assignés à l'utilisateur
     */
    public function getUserDepotIds($user): array
    {
        if (!$user || !method_exists($user, 'depots')) {
            return [];
        }

        try {
            return $user->depots()->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Vérifie si l'utilisateur a une permission
     */
    private function hasPermission(array $permissions, string $permission): bool
    {
        if (in_array('*', $permissions, true)) {
            return true;
        }
        return in_array($permission, $permissions, true);
    }

    /**
     * Obtient le dépôt ID à utiliser pour la création/modification
     * Retourne null si l'utilisateur n'a pas accès au dépôt sélectionné
     */
    public function getEffectiveDepotId(Request $request): ?int
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        $currentDepotId = $request->session()->get('current_depot_id');
        if (!$currentDepotId) {
            return null; // Dépôt central
        }

        $permissions = $user->permissionCodes();

        // Si view_all_warehouse, peut utiliser n'importe quel dépôt
        if ($this->hasPermission($permissions, self::PERMISSION_VIEW_ALL)) {
            return (int) $currentDepotId;
        }

        // Si view_warehouse, vérifier que le dépôt est assigné à l'utilisateur
        if ($this->hasPermission($permissions, self::PERMISSION_VIEW)) {
            $userDepotIds = $this->getUserDepotIds($user);
            if (in_array((int) $currentDepotId, $userDepotIds, true)) {
                return (int) $currentDepotId;
            }
        }

        // Pas d'accès : retourner null (dépôt central)
        return null;
    }
}
