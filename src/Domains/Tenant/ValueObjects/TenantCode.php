<?php

namespace Domains\Tenant\ValueObjects;

/**
 * Value Object TenantCode
 *
 * Représente le code unique et immutable d'un tenant.
 * Le code est auto-validé et peut être utilisé pour identifier un tenant de manière lisible.
 *
 * Format requis:
 * - Longueur: 3-10 caractères
 * - Caractères: Majuscules et chiffres uniquement
 * - Aucun espace ou caractère spécial
 *
 * Exemple: "SHOP001", "CAFE2024"
 */
final class TenantCode
{
    /**
     * @var string La valeur immuable du code
     */
    private readonly string $value;

    /**
     * Constructeur du Value Object
     *
     * @param string $value Le code du tenant à valider et stocker
     * @throws \InvalidArgumentException Si le code ne respecte pas les règles métier
     */
    public function __construct(string $value)
    {
        // Valider la longueur
        if (strlen($value) < 3 || strlen($value) > 10) {
            throw new \InvalidArgumentException(
                'TenantCode must be between 3 and 10 characters'
            );
        }

        // Valider le format (majuscules et chiffres uniquement)
        if (!preg_match('/^[A-Z0-9]+$/', $value)) {
            throw new \InvalidArgumentException(
                'TenantCode must contain only uppercase letters and numbers'
            );
        }

        // Stocker la valeur validée
        $this->value = $value;
    }

    /**
     * Obtenir la valeur du code
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Comparer deux codes pour égalité
     *
     * @param TenantCode $other Le code à comparer
     * @return bool true si les codes sont identiques
     */
    public function equals(TenantCode $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Représentation string du code
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
