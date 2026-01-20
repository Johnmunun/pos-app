<?php

namespace Domains\User\Entities;

use Domains\User\ValueObjects\Email;
use Domains\User\ValueObjects\Password;
use Domains\User\ValueObjects\UserType;
use Domains\Tenant\Entities\Tenant;

/**
 * Entity User (Agrégat Racine)
 *
 * Représente un utilisateur de l'application POS SaaS.
 *
 * Un utilisateur peut être:
 * - ROOT: Propriétaire de l'application
 * - TENANT_ADMIN: Administrateur d'un tenant
 * - MERCHANT, SELLER, STAFF: Utilisateurs avec permissions
 *
 * Les permissions précises sont gérées dans le domain AccessControl.
 *
 * Caractéristiques:
 * - Email unique
 * - Password hashé (jamais en clair)
 * - Type d'utilisateur (ROOT, TENANT_ADMIN, etc.)
 * - Optionnel: Associated tenant_id (null pour ROOT)
 * - Status: actif/inactif
 */
class User
{
    private ?int $id;
    private Email $email;
    private Password $password;
    private string $firstName;
    private string $lastName;
    private UserType $type;
    private ?int $tenantId;         // null pour ROOT, sinon l'ID du tenant
    private bool $isActive;
    private ?\DateTime $lastLoginAt;
    private \DateTime $createdAt;
    private ?\DateTime $updatedAt;

    /**
     * Constructeur privé
     */
    private function __construct() {}

    /**
     * Factory Method: Créer un nouvel utilisateur (pas encore persisté)
     *
     * @param string $email Email unique
     * @param string $plainPassword Mot de passe en clair (à valider)
     * @param string $firstName Prénom
     * @param string $lastName Nom
     * @param string $type Type d'utilisateur (ROOT, TENANT_ADMIN, etc.)
     * @param int|null $tenantId ID du tenant (null pour ROOT)
     *
     * @return self
     * @throws \InvalidArgumentException Si validation échoue
     */
    public static function createNew(
        string $email,
        string $plainPassword,
        string $firstName,
        string $lastName,
        string $type,
        ?int $tenantId = null
    ): self {
        $user = new self();

        // Valider et assigner les value objects
        $user->email = new Email($email);
        $user->password = new Password($plainPassword);
        $user->firstName = trim($firstName);
        $user->lastName = trim($lastName);
        $user->type = new UserType($type);
        $user->tenantId = $tenantId;

        // État par défaut
        $user->id = null;
        $user->isActive = true;
        $user->lastLoginAt = null;
        $user->createdAt = new \DateTime();
        $user->updatedAt = null;

        return $user;
    }

    /**
     * Factory Method: Créer l'utilisateur ROOT initial
     *
     * Cas spécial pour la création du premier utilisateur ROOT
     *
     * @param string $email
     * @param string $plainPassword
     * @param string $firstName
     * @param string $lastName
     * @return self
     */
    public static function createRootUser(
        string $email,
        string $plainPassword,
        string $firstName,
        string $lastName
    ): self {
        return self::createNew(
            email: $email,
            plainPassword: $plainPassword,
            firstName: $firstName,
            lastName: $lastName,
            type: UserType::ROOT,
            tenantId: null  // ROOT n'est associé à aucun tenant
        );
    }

    /**
     * Factory Method: Hydrater depuis la DB
     *
     * @param int $id
     * @param string $email
     * @param string $passwordHash Hash du mot de passe
     * @param string $firstName
     * @param string $lastName
     * @param string $type
     * @param int|null $tenantId
     * @param bool $isActive
     * @param \DateTime|null $lastLoginAt
     * @param \DateTime $createdAt
     * @param \DateTime|null $updatedAt
     *
     * @return self
     */
    public static function hydrate(
        int $id,
        string $email,
        string $passwordHash,
        string $firstName,
        string $lastName,
        string $type,
        ?int $tenantId,
        bool $isActive,
        ?\DateTime $lastLoginAt,
        \DateTime $createdAt,
        ?\DateTime $updatedAt = null
    ): self {
        $user = new self();

        $user->id = $id;
        $user->email = new Email($email);
        $user->password = Password::fromHash($passwordHash);
        $user->firstName = $firstName;
        $user->lastName = $lastName;
        $user->type = new UserType($type);
        $user->tenantId = $tenantId;
        $user->isActive = $isActive;
        $user->lastLoginAt = $lastLoginAt;
        $user->createdAt = $createdAt;
        $user->updatedAt = $updatedAt;

        return $user;
    }

    // Getters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isRoot(): bool
    {
        return $this->type->isRoot();
    }

    public function getLastLoginAt(): ?\DateTime
    {
        return $this->lastLoginAt;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Obtenir le hash du mot de passe
     *
     * Utilisé par le repository pour la persistance
     *
     * @return string
     */
    public function getPasswordHash(): string
    {
        return $this->password->getHash();
    }

    // Métiers

    /**
     * Vérifier le mot de passe
     *
     * @param string $plainPassword Le mot de passe en clair à vérifier
     * @return bool
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return $this->password->verify($plainPassword);
    }

    /**
     * Marquer une connexion
     *
     * Met à jour lastLoginAt avec le timestamp actuel
     */
    public function markAsLoggedIn(): void
    {
        $this->lastLoginAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Activer l'utilisateur
     *
     * @throws \Exception Si déjà actif
     */
    public function activate(): void
    {
        if ($this->isActive) {
            throw new \Exception('User is already active');
        }
        $this->isActive = true;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Désactiver l'utilisateur
     *
     * @throws \Exception Si déjà inactif
     */
    public function deactivate(): void
    {
        if (!$this->isActive) {
            throw new \Exception('User is already inactive');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Changer le mot de passe
     *
     * @param string $newPlainPassword Le nouveau mot de passe en clair
     * @throws \InvalidArgumentException Si le mot de passe ne respecte pas les critères
     */
    public function changePassword(string $newPlainPassword): void
    {
        $this->password = new Password($newPlainPassword);
        $this->updatedAt = new \DateTime();
    }

    /**
     * Assigner l'ID après persistence
     *
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
