<?php

declare(strict_types=1);

namespace Src\Application\Quincaillerie\DTO;

/**
 * DTO: UpdateSupplierDTO
 *
 * Data Transfer Object pour la mise à jour d'un fournisseur.
 * Tous les champs sont optionnels pour supporter les mises à jour partielles.
 */
final readonly class UpdateSupplierDTO
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $contactPerson = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $address = null
    ) {
    }
}
