<?php

namespace Domains\Tenant\ValueObjects;

/**
 * Value Object TenantName
 *
 * Représente le nom commercial d'un tenant.
 * Valide et immuable.
 *
 * Le nom est le nom public/commercial du tenant
 * Exemple: "Ma Boutique SARL", "Supermarché Central"
 */
final class TenantName
{
    /**
     * @var string La valeur immuable du nom
     */
    private readonly string $value;

    /**
     * Constructeur du Value Object
     *
     * @param string $value Le nom du tenant
     * @throws \InvalidArgumentException Si le nom ne respecte pas les critères
     */
    public function __construct(string $value)
    {
        // Nettoyer les espaces
        $value = trim($value);

        // Valider la longueur
        if (strlen($value) < 3 || strlen($value) > 255) {
            throw new \InvalidArgumentException(
                'TenantName must be between 3 and 255 characters'
            );
        }

        // Valider que ce n'est pas vide après nettoyage
        if (empty($value)) {
            throw new \InvalidArgumentException(
                'TenantName cannot be empty'
            );
        }

        $this->value = $value;
    }

    /**
     * Obtenir la valeur du nom
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Comparer deux noms pour égalité
     *
     * @param TenantName $other Le nom à comparer
     * @return bool true si les noms sont identiques
     */
    public function equals(TenantName $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Représentation string du nom
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
