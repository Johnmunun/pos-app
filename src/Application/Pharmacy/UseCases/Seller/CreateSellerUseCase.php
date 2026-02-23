<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\Seller;

use Src\Application\Pharmacy\DTO\CreateSellerDTO;
use Domains\User\Services\UserService;
use Src\Domains\User\UseCases\AssignUserRoleUseCase;
use Src\Domains\User\Services\ModulePermissionService;
use App\Models\User as UserModel;
use App\Models\Role;
use App\Models\Tenant;
use InvalidArgumentException;
use RuntimeException;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: CreateSellerUseCase
 *
 * Crée un nouveau vendeur pour une pharmacie avec assignation de rôles.
 * Seuls les TENANT_ADMIN et MERCHANT peuvent créer des vendeurs pour leur tenant.
 */
final class CreateSellerUseCase
{
    public function __construct(
        private readonly UserService $userService,
        private readonly AssignUserRoleUseCase $assignRoleUseCase,
        private readonly ModulePermissionService $modulePermissionService
    ) {
    }

    /**
     * Créer un vendeur avec assignation de rôles
     *
     * @param CreateSellerDTO $dto
     * @return UserModel
     * @throws InvalidArgumentException Si les données sont invalides
     * @throws RuntimeException Si l'utilisateur créé n'a pas d'ID
     */
    public function execute(CreateSellerDTO $dto): UserModel
    {
        // Valider les données
        if (empty(trim($dto->firstName))) {
            throw new InvalidArgumentException('Le prénom est obligatoire.');
        }

        if (empty(trim($dto->lastName))) {
            throw new InvalidArgumentException('Le nom est obligatoire.');
        }

        if (empty(trim($dto->email)) || !filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L\'email est invalide.');
        }

        if (strlen($dto->password) < 8) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        // Vérifier que l'email n'existe pas déjà
        if (UserModel::where('email', $dto->email)->exists()) {
            throw new InvalidArgumentException('Un utilisateur avec cet email existe déjà.');
        }

        // Créer l'utilisateur via le service de domaine
        $userEntity = $this->userService->createUser(
            email: $dto->email,
            plainPassword: $dto->password,
            firstName: $dto->firstName,
            lastName: $dto->lastName,
            type: 'SELLER',
            tenantId: $dto->tenantId
        );

        // Récupérer le modèle Eloquent depuis l'ID
        $userId = $userEntity->getId();
        if ($userId === null) {
            throw new \RuntimeException('L\'utilisateur créé n\'a pas d\'ID. Erreur de persistance.');
        }
        $userModel = UserModel::findOrFail($userId);

        // Mettre à jour le statut si nécessaire
        if (!$dto->isActive) {
            $userModel->update(['status' => 'pending']);
        }

        // Assigner les rôles si fournis
        // Sécurité : Vérifier que les rôles appartiennent au tenant ou sont globaux avec permissions secteur
        $roleIds = $dto->roleIds;
        if (is_array($roleIds) && $roleIds !== []) {
            // Récupérer le secteur d'activité du tenant
            $tenant = Tenant::find($dto->tenantId);
            $sector = $tenant?->sector;
            
            // Obtenir les permissions autorisées pour le secteur
            $authorizedPermissions = $this->modulePermissionService->getAuthorizedPermissions($sector);
            
            // Rôles du tenant OU rôles globaux (tenant_id null) avec permissions secteur uniquement
            $roles = Role::whereIn('id', $roleIds)
                ->where(function ($q) use ($dto) {
                    $q->where('tenant_id', $dto->tenantId)->orWhereNull('tenant_id');
                })
                ->with('permissions')
                ->get();
            
            $validRoles = [];
            foreach ($roles as $role) {
                // Vérifier que toutes les permissions du rôle sont autorisées pour le secteur
                $rolePermissions = $role->permissions->pluck('code')->toArray();
                $isValid = true;
                foreach ($rolePermissions as $perm) {
                    if (!in_array($perm, $authorizedPermissions)) {
                        $isValid = false;
                        Log::warning('Role has non-authorized permissions for sector, skipping', [
                            'role_id' => $role->id,
                            'role_name' => $role->name,
                            'sector' => $sector,
                            'invalid_permission' => $perm,
                        ]);
                        break;
                    }
                }
                if ($isValid && !empty($rolePermissions)) {
                    $validRoles[] = $role->id;
                }
            }

            if (count($validRoles) !== count($roleIds)) {
                Log::warning('Some roles were invalid for tenant', [
                    'tenant_id' => $dto->tenantId,
                    'requested_roles' => $roleIds,
                    'valid_roles' => $validRoles,
                ]);
            }

            if (!empty($validRoles)) {
                try {
                    $this->assignRoleUseCase->assignRolesForTenant(
                        userId: $userModel->id,
                        roleIds: $validRoles,
                        tenantId: $dto->tenantId
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to assign roles to seller', [
                        'user_id' => $userModel->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Recharger les relations
        $userModel->refresh();
        $userModel->load(['roles', 'tenant']);

        return $userModel;
    }
}
