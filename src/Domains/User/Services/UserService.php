<?php

namespace Domains\User\Services;

use Domains\User\Entities\User;
use Domains\User\Repositories\UserRepository;
use Domains\User\ValueObjects\UserType;

/**
 * Domain Service: UserService
 *
 * Encapsule la logique métier du domaine User.
 *
 * Responsabilités:
 * - Créer de nouveaux utilisateurs
 * - Vérifier les authentifications
 * - Gérer les activations/désactivations
 * - Valider l'unicité des emails
 */
class UserService
{
    public function __construct(
        private UserRepository $repository
    ) {}

    /**
     * Créer un nouvel utilisateur ROOT
     *
     * ⚠️ OPÉRATION SÉCRITAIRE: Vérifier qu'aucun ROOT n'existe déjà!
     *
     * @param string $email
     * @param string $plainPassword
     * @param string $firstName
     * @param string $lastName
     *
     * @return User
     * @throws \Exception Si un ROOT existe déjà
     * @throws \Exception Si l'email existe déjà
     */
    public function createRootUser(
        string $email,
        string $plainPassword,
        string $firstName,
        string $lastName
    ): User {
        // Vérifier qu'aucun ROOT n'existe
        if ($this->repository->rootExists()) {
            throw new \Exception(
                'A ROOT user already exists. Only one ROOT user is allowed.'
            );
        }

        // Vérifier l'unicité de l'email
        if ($this->repository->emailExists($email)) {
            throw new \Exception(
                "A user with email '{$email}' already exists."
            );
        }

        // Créer l'entity
        $user = User::createRootUser(
            email: $email,
            plainPassword: $plainPassword,
            firstName: $firstName,
            lastName: $lastName
        );

        // Persister
        return $this->repository->save($user);
    }

    /**
     * Créer un nouvel utilisateur non-ROOT
     *
     * @param string $email
     * @param string $plainPassword
     * @param string $firstName
     * @param string $lastName
     * @param string $type Type d'utilisateur
     * @param int $tenantId ID du tenant auquel appartient l'utilisateur
     *
     * @return User
     * @throws \Exception Si l'email existe déjà
     */
    public function createUser(
        string $email,
        string $plainPassword,
        string $firstName,
        string $lastName,
        string $type,
        int $tenantId
    ): User {
        // Vérifier l'unicité de l'email
        if ($this->repository->emailExists($email)) {
            throw new \Exception(
                "A user with email '{$email}' already exists."
            );
        }

        // Créer l'entity
        $user = User::createNew(
            email: $email,
            plainPassword: $plainPassword,
            firstName: $firstName,
            lastName: $lastName,
            type: $type,
            tenantId: $tenantId
        );

        // Persister
        return $this->repository->save($user);
    }

    /**
     * Authentifier un utilisateur
     *
     * @param string $email
     * @param string $plainPassword
     *
     * @return User|null L'utilisateur si authentification réussie, null sinon
     */
    public function authenticate(string $email, string $plainPassword): ?User
    {
        // Trouver l'utilisateur par email
        $user = $this->repository->findByEmail($email);

        if (!$user) {
            return null;
        }

        // Vérifier le mot de passe
        if (!$user->verifyPassword($plainPassword)) {
            return null;
        }

        // Vérifier que l'utilisateur est actif
        if (!$user->isActive()) {
            return null;
        }

        // Marquer la connexion
        $user->markAsLoggedIn();
        $this->repository->save($user);

        return $user;
    }

    /**
     * Obtenir un utilisateur par ID
     *
     * @param int $id
     * @return User
     * @throws \Exception Si non trouvé
     */
    public function getUser(int $id): User
    {
        $user = $this->repository->findById($id);

        if (!$user) {
            throw new \Exception("User with ID {$id} not found");
        }

        return $user;
    }

    /**
     * Obtenir l'utilisateur ROOT
     *
     * @return User|null
     */
    public function getRootUser(): ?User
    {
        return $this->repository->findRoot();
    }
}
