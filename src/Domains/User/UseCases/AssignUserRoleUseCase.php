<?php

namespace Src\Domains\User\UseCases;

use Domains\User\Repositories\UserRepository;
use App\Models\User as UserModel;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: AssignUserRoleUseCase
 *
 * Assigner un rôle à un utilisateur.
 * ROOT peut assigner n'importe quel rôle.
 *
 * @package Src\Domains\User\UseCases
 */
class AssignUserRoleUseCase
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * Assigner un rôle à un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param int $roleId ID du rôle à assigner
     * @param int|null $tenantId ID du tenant (optionnel, pour les rôles multi-tenant)
     * @throws \Exception Si l'utilisateur ou le rôle n'existe pas
     */
    public function execute(int $userId, int $roleId, ?int $tenantId = null): void
    {
        $userModel = UserModel::findOrFail($userId);
        $role = Role::findOrFail($roleId);

        // Vérifier que l'utilisateur n'est pas ROOT (protection)
        if ($userModel->isRoot()) {
            throw new \Exception('Impossible de modifier les rôles d\'un utilisateur ROOT');
        }

        // Vérifier que le rôle a des permissions
        $role->load('permissions');
        if ($role->permissions->isEmpty()) {
            throw new \Exception('Ce rôle n\'a aucune permission. Veuillez d\'abord assigner des permissions au rôle.');
        }

        // Synchroniser les rôles (remplace les rôles existants pour ce tenant)
        if ($tenantId) {
            // Retirer les rôles existants pour ce tenant
            DB::table('user_role')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->delete();

            // Assigner le nouveau rôle
            DB::table('user_role')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // Assigner le rôle globalement
            // Utiliser attach pour éviter les doublons (la contrainte unique dans la table le gère)
            $existingRoles = $userModel->roles()->where('roles.id', $roleId)->exists();
            if (!$existingRoles) {
                $userModel->roles()->attach($roleId);
            }
        }

        // Forcer le rechargement des relations pour que les permissions soient à jour
        $userModel->refresh();
        $userModel->load('roles.permissions');
        
        // Vérifier que les permissions sont bien chargées
        $permissions = $userModel->permissionCodes();
        if (empty($permissions)) {
            throw new \Exception('Aucune permission trouvée après l\'assignation du rôle. Vérifiez que le rôle a des permissions.');
        }
    }

    /**
     * Assigner plusieurs rôles à un utilisateur pour un tenant (remplace les rôles existants pour ce tenant).
     *
     * @param int $userId
     * @param array<int, int> $roleIds
     * @param int $tenantId
     * @throws \Exception
     */
    public function assignRolesForTenant(int $userId, array $roleIds, int $tenantId): void
    {
        $userModel = UserModel::findOrFail($userId);
        if ($userModel->isRoot()) {
            throw new \Exception('Impossible de modifier les rôles d\'un utilisateur ROOT');
        }

        DB::table('user_role')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->delete();

        // Dédupliquer pour éviter "Duplicate entry" sur (user_id, role_id, tenant_id)
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

        $now = now();
        foreach ($roleIds as $roleId) {
            $roleIdInt = (int) $roleId;
            $role = Role::find($roleIdInt);
            if (!$role || $role->permissions()->count() === 0) {
                continue;
            }
            DB::table('user_role')->insert([
                'user_id' => $userId,
                'role_id' => $roleIdInt,
                'tenant_id' => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $userModel->refresh();
        $userModel->load('roles.permissions');
    }
}
