<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Service pour gérer le rôle ROOT et ses permissions
 */
class RootRoleService
{
    /**
     * Assure que le rôle ROOT existe, le crée si nécessaire
     * 
     * @return Role
     */
    public function ensureRootRole(): Role
    {
        $role = Role::where('name', 'ROOT')
            ->orWhere('name', 'Administrateur Principal')
            ->first();

        if (!$role) {
            $role = Role::create([
                'name' => 'ROOT',
                'description' => 'Administrateur Principal - Accès complet à tous les tenants et gestion globale',
                'tenant_id' => null, // ROOT n'est pas lié à un tenant spécifique
                'is_active' => true,
            ]);
        } else {
            // S'assurer que le rôle est actif
            if (!$role->is_active) {
                $role->update(['is_active' => true]);
            }
        }

        return $role;
    }

    /**
     * Synchronise toutes les permissions actives vers le rôle ROOT
     * 
     * @param Role $role
     * @return void
     */
    public function syncRolePermissions(Role $role): void
    {
        // Récupérer toutes les permissions actives (non obsolètes)
        $permissions = Permission::where('is_old', false)->pluck('id');

        // Supprimer toutes les permissions actuelles du rôle
        DB::table('role_permission')
            ->where('role_id', $role->id)
            ->delete();

        // Assigner toutes les permissions au rôle ROOT
        $permissionsData = $permissions->map(function ($permissionId) use ($role) {
            return [
                'role_id' => $role->id,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        if (!empty($permissionsData)) {
            DB::table('role_permission')->insert($permissionsData);
        }
    }

    /**
     * Assigner le rôle ROOT à un utilisateur
     * 
     * @param User $user
     * @param Role $role
     * @return void
     */
    public function assignRoleToUser(User $user, Role $role): void
    {
        // Vérifier si l'utilisateur a déjà ce rôle
        $hasRole = DB::table('user_role')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->exists();

        if (!$hasRole) {
            DB::table('user_role')->insert([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'tenant_id' => null, // ROOT n'est pas lié à un tenant
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
