<?php

namespace Domains\User\Repositories;

use Domains\User\Entities\User;

/**
 * Repository Interface UserRepository
 *
 * Définit le contrat pour accéder aux données des utilisateurs.
 * L'implémentation utilise Eloquent.
 */
interface UserRepository
{
    /**
     * Trouver un utilisateur par ID
     */
    public function findById(int $id): ?User;

    /**
     * Trouver un utilisateur par email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Lister tous les utilisateurs d'un tenant
     *
     * @param int $tenantId
     * @return User[]
     */
    public function findByTenantId(int $tenantId): array;

    /**
     * Trouver l'utilisateur ROOT
     *
     * @return User|null
     */
    public function findRoot(): ?User;

    /**
     * Sauvegarder un utilisateur
     */
    public function save(User $user): User;

    /**
     * Supprimer un utilisateur
     */
    public function delete(int $id): bool;

    /**
     * Vérifier si un email existe
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;

    /**
     * Compter les utilisateurs d'un tenant
     */
    public function countByTenantId(int $tenantId): int;

    /**
     * Vérifier si un utilisateur ROOT existe
     */
    public function rootExists(): bool;
}
