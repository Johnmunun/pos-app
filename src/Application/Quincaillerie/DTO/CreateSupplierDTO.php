<?php

declare(strict_types=1);

namespace Src\Application\Quincaillerie\DTO;

/**
 * DTO: CreateSupplierDTO
 *
 * Data Transfer Object pour la création d'un fournisseur.
 */
final readonly class CreateSupplierDTO
{
    public function __construct(
        public int $shopId,
        public string $name,
        public ?string $contactPerson = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $address = null
    ) {
    }
}
