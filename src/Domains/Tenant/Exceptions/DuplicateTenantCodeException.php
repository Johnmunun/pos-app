<?php

namespace Domains\Tenant\Exceptions;

/**
 * Exception DuplicateTenantCodeException
 *
 * Levée quand on tente de créer un tenant avec un code déjà existant.
 * Le code doit être unique dans le système.
 */
class DuplicateTenantCodeException extends \Exception
{
    /**
     * Un tenant avec ce code existe déjà
     *
     * @param string $code Le code dupliqué
     * @return self
     */
    public static function withCode(string $code): self
    {
        return new self(
            "A tenant with code '{$code}' already exists. Codes must be unique."
        );
    }
}
