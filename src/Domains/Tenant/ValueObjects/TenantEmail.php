<?php

namespace Domains\Tenant\ValueObjects;

/**
 * Value Object TenantEmail
 *
 * Représente l'adresse email de contact du tenant.
 * Valide et immuable.
 *
 * Email pour contacter le responsable du tenant
 */
final class TenantEmail
{
    /**
     * @var string La valeur immuable de l'email
     */
    private readonly string $value;

    /**
     * Constructeur du Value Object
     *
     * @param string $value L'email du tenant
     * @throws \InvalidArgumentException Si l'email n'est pas valide
     */
    public function __construct(string $value)
    {
        // Nettoyer les espaces
        $value = trim($value);
        $value = strtolower($value);

        // Valider le format email
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                'TenantEmail must be a valid email address'
            );
        }

        // Vérifier la longueur
        if (strlen($value) > 254) {
            throw new \InvalidArgumentException(
                'TenantEmail must not exceed 254 characters'
            );
        }

        $this->value = $value;
    }

    /**
     * Obtenir la valeur de l'email
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Obtenir le domaine de l'email
     *
     * @return string
     */
    public function getDomain(): string
    {
        return explode('@', $this->value)[1] ?? '';
    }

    /**
     * Comparer deux emails pour égalité
     *
     * @param TenantEmail $other L'email à comparer
     * @return bool true si les emails sont identiques
     */
    public function equals(TenantEmail $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Représentation string de l'email
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
