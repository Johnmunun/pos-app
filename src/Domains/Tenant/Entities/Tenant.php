<?php

namespace Domains\Tenant\Entities;

use Domains\Tenant\ValueObjects\TenantCode;
use Domains\Tenant\ValueObjects\TenantName;
use Domains\Tenant\ValueObjects\TenantEmail;
use Domains\Tenant\Exceptions\InvalidTenantStateException;

/**
 * Entity Tenant (Agrégat Racine)
 *
 * Représente un commerçant/boutique dans le système SaaS POS.
 * Chaque tenant est complètement isolé avec ses propres données (multi-tenancy).
 *
 * Responsabilités:
 * - Gestion du cycle de vie du tenant (creation, activation, deactivation)
 * - Validation des règles métier du tenant
 * - Immuabilité des données sensibles (code)
 *
 * Cette entity encapsule TOUTE la logique métier relative aux tenants.
 * Les controllers et repositories n'implémentent PAS cette logique.
 */
class Tenant
{
    /**
     * @var int|null L'ID unique du tenant en base de données (null si new)
     */
    private ?int $id;

    /**
     * @var TenantCode Le code unique et immutable du tenant
     */
    private TenantCode $code;

    /**
     * @var TenantName Le nom commercial du tenant
     */
    private TenantName $name;

    /**
     * @var TenantEmail L'email de contact du tenant
     */
    private TenantEmail $email;

    /**
     * @var bool État d'activation du tenant
     */
    private bool $isActive;

    /**
     * @var \DateTime Date de création du tenant
     */
    private \DateTime $createdAt;

    /**
     * @var \DateTime|null Date de dernière modification
     */
    private ?\DateTime $updatedAt;

    /**
     * Constructeur privé - utiliser createNew() ou hydrate()
     */
    private function __construct() {}

    /**
     * Factory Method: Créer un nouveau tenant (pas encore persisté)
     *
     * @param string $code Code unique du tenant (validé automatiquement)
     * @param string $name Nom commercial du tenant
     * @param string $email Email de contact
     *
     * @return self Une nouvelle instance de Tenant
     * @throws \InvalidArgumentException Si une validation échoue
     */
    public static function createNew(
        string $code,
        string $name,
        string $email
    ): self {
        $tenant = new self();

        // Valider et assigner les value objects
        $tenant->code = new TenantCode($code);
        $tenant->name = new TenantName($name);
        $tenant->email = new TenantEmail($email);

        // État par défaut
        $tenant->id = null;
        $tenant->isActive = true;
        $tenant->createdAt = new \DateTime();
        $tenant->updatedAt = null;

        return $tenant;
    }

    /**
     * Factory Method: Hydrater un tenant depuis la base de données
     *
     * Utilisé par le repository pour recréer une entity à partir des données persistées.
     *
     * @param int $id L'ID du tenant en base
     * @param string $code Le code du tenant
     * @param string $name Le nom du tenant
     * @param string $email L'email du tenant
     * @param bool $isActive État d'activation
     * @param \DateTime $createdAt Date de création
     * @param \DateTime|null $updatedAt Date de modification
     *
     * @return self L'instance hydratée
     */
    public static function hydrate(
        int $id,
        string $code,
        string $name,
        string $email,
        bool $isActive,
        \DateTime $createdAt,
        ?\DateTime $updatedAt = null
    ): self {
        $tenant = new self();

        $tenant->id = $id;
        $tenant->code = new TenantCode($code);
        $tenant->name = new TenantName($name);
        $tenant->email = new TenantEmail($email);
        $tenant->isActive = $isActive;
        $tenant->createdAt = $createdAt;
        $tenant->updatedAt = $updatedAt;

        return $tenant;
    }

    /**
     * Obtenir l'ID du tenant
     *
     * @return int|null null si pas encore persisté
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Obtenir le code unique du tenant
     *
     * @return TenantCode
     */
    public function getCode(): TenantCode
    {
        return $this->code;
    }

    /**
     * Obtenir le nom commercial du tenant
     *
     * @return TenantName
     */
    public function getName(): TenantName
    {
        return $this->name;
    }

    /**
     * Obtenir l'email du tenant
     *
     * @return TenantEmail
     */
    public function getEmail(): TenantEmail
    {
        return $this->email;
    }

    /**
     * Vérifier si le tenant est actif
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Obtenir la date de création
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Obtenir la date de modification
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Activer le tenant
     *
     * Opération métier: change l'état d'inactivité à activité.
     * Lance une exception si déjà actif.
     *
     * @return void
     * @throws InvalidTenantStateException Si le tenant est déjà actif
     */
    public function activate(): void
    {
        if ($this->isActive) {
            throw InvalidTenantStateException::alreadyActive($this->id);
        }

        $this->isActive = true;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Désactiver le tenant
     *
     * Opération métier: change l'état d'activité à inactivité.
     * Lance une exception si déjà inactif.
     *
     * @return void
     * @throws InvalidTenantStateException Si le tenant est déjà inactif
     */
    public function deactivate(): void
    {
        if (!$this->isActive) {
            throw InvalidTenantStateException::alreadyInactive($this->id);
        }

        $this->isActive = false;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Mettre à jour le nom du tenant
     *
     * @param string $newName Le nouveau nom
     * @return void
     * @throws \InvalidArgumentException Si le nom n'est pas valide
     */
    public function updateName(string $newName): void
    {
        $this->name = new TenantName($newName);
        $this->updatedAt = new \DateTime();
    }

    /**
     * Mettre à jour l'email du tenant
     *
     * @param string $newEmail Le nouvel email
     * @return void
     * @throws \InvalidArgumentException Si l'email n'est pas valide
     */
    public function updateEmail(string $newEmail): void
    {
        $this->email = new TenantEmail($newEmail);
        $this->updatedAt = new \DateTime();
    }

    /**
     * Marquer le tenant comme modifié (pour persistance)
     *
     * @return void
     */
    public function markAsModified(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Assigner l'ID après persistence en base
     *
     * Utilisé uniquement par le repository après création en DB.
     *
     * @param int $id L'ID généré par la base de données
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
