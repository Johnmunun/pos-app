<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RootRoleService
{
    /**
     * Créer ou récupérer le rôle ROOT global.
     */
    public function ensureRootRole(): Role
    {
        return Role::firstOrCreate(
            ['tenant_id' => null, 'name' => 'ROOT'],
            [
                'description' => 'Rôle global ROOT (accès total via permissions)',
                'is_active' => true,
            ]
        );
    }

    /**
     * Assigner toutes les permissions au rôle ROOT.
     */
    public function syncRolePermissions(Role $role): void
    {
        $permissionIds = DB::table('permissions')->pluck('id')->all();

        if (!empty($permissionIds)) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }

    /**
     * Assigner le rôle ROOT à l'utilisateur.
     */
    public function assignRoleToUser(User $user, Role $role): void
    {
        $user->roles()->syncWithoutDetaching([
            $role->id => ['tenant_id' => null],
        ]);
    }
}



