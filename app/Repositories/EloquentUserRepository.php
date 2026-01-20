<?php

namespace App\Repositories;

use App\Models\User as UserModel;
use Domains\User\Entities\User;
use Domains\User\Repositories\UserRepository;

/**
 * Repository Implementation: EloquentUserRepository
 *
 * Implémente UserRepository du domain avec Eloquent.
 */
class EloquentUserRepository implements UserRepository
{
    /**
     * Trouver un utilisateur par ID
     */
    public function findById(int $id): ?User
    {
        $model = UserModel::find($id);
        return $model ? $this->modelToEntity($model) : null;
    }

    /**
     * Trouver un utilisateur par email
     */
    public function findByEmail(string $email): ?User
    {
        $model = UserModel::where('email', strtolower($email))->first();
        return $model ? $this->modelToEntity($model) : null;
    }

    /**
     * Lister tous les utilisateurs d'un tenant
     */
    public function findByTenantId(int $tenantId): array
    {
        return UserModel::where('tenant_id', $tenantId)
            ->get()
            ->map(fn($model) => $this->modelToEntity($model))
            ->toArray();
    }

    /**
     * Trouver l'utilisateur ROOT
     */
    public function findRoot(): ?User
    {
        $model = UserModel::where('type', 'ROOT')->first();
        return $model ? $this->modelToEntity($model) : null;
    }

    /**
     * Sauvegarder un utilisateur
     */
    public function save(User $user): User
    {
        $data = [
            'email' => $user->getEmail()->getValue(),
            'password' => $user->getPasswordHash(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'type' => $user->getType()->getValue(),
            'tenant_id' => $user->getTenantId(),
            'is_active' => $user->isActive(),
        ];

        if ($user->getId()) {
            $model = UserModel::findOrFail($user->getId());
            $model->update($data);
        } else {
            $model = UserModel::create($data);
            $user->setId($model->id);
        }

        return $user;
    }

    /**
     * Supprimer un utilisateur
     */
    public function delete(int $id): bool
    {
        $model = UserModel::find($id);
        return $model ? $model->delete() : false;
    }

    /**
     * Vérifier si un email existe
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = UserModel::where('email', strtolower($email));
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * Compter les utilisateurs d'un tenant
     */
    public function countByTenantId(int $tenantId): int
    {
        return UserModel::where('tenant_id', $tenantId)->count();
    }

    /**
     * Vérifier si un utilisateur ROOT existe
     */
    public function rootExists(): bool
    {
        return UserModel::where('type', 'ROOT')->exists();
    }

    /**
     * Helper: Convertir Model → Entity
     *
     * ⚠️ IMPORTANT: Pour hydrater, il faudrait accéder au password hash
     * Actuellement, User::hydrate() attend le hash mais c'est pas exposé par getPassword()
     * Solution: Modifier User::hydrate() ou exposer getPasswordHash()
     */
    private function modelToEntity(UserModel $model): User
    {
        return User::hydrate(
            id: $model->id,
            email: $model->email,
            passwordHash: $model->password,
            firstName: $model->first_name,
            lastName: $model->last_name,
            type: $model->type,
            tenantId: $model->tenant_id,
            isActive: $model->is_active,
            lastLoginAt: $model->last_login_at,
            createdAt: $model->created_at ?? new \DateTime(),
            updatedAt: $model->updated_at
        );
    }
}
