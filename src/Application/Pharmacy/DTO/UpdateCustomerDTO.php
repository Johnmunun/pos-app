<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\DTO;

/**
 * DTO: UpdateCustomerDTO
 *
 * Données pour mettre à jour un client existant.
 */
final readonly class UpdateCustomerDTO
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $address = null,
        public ?string $customerType = null,
        public ?string $taxNumber = null,
        public ?float $creditLimit = null
    ) {
    }
}
