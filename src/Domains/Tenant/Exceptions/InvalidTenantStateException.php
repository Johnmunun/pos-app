<?php

namespace Domains\Tenant\Exceptions;

/**
 * Exception InvalidTenantStateException
 *
 * Levée quand on tente une opération invalide sur un tenant.
 * Exemples:
 * - Activer un tenant déjà actif
 * - Désactiver un tenant déjà inactif
 * - Modifier un tenant supprimé
 */
class InvalidTenantStateException extends \Exception
{
    /**
     * Le tenant est déjà actif
     *
     * @param int|null $tenantId
     * @return self
     */
    public static function alreadyActive(?int $tenantId): self
    {
        return new self(
            "Tenant {$tenantId} is already active. Cannot activate it again."
        );
    }

    /**
     * Le tenant est déjà inactif
     *
     * @param int|null $tenantId
     * @return self
     */
    public static function alreadyInactive(?int $tenantId): self
    {
        return new self(
            "Tenant {$tenantId} is already inactive. Cannot deactivate it again."
        );
    }

    /**
     * Le tenant n'existe pas
     *
     * @param int|null $tenantId
     * @return self
     */
    public static function notFound(?int $tenantId): self
    {
        return new self(
            "Tenant with ID {$tenantId} not found."
        );
    }
}
