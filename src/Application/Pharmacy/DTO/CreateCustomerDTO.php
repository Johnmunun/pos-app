<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\DTO;

/**
 * DTO: CreateCustomerDTO
 *
 * Données pour créer un nouveau client.
 */
final readonly class CreateCustomerDTO
{
    public function __construct(
        public int $shopId,
        public string $name,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $address = null,
        public string $customerType = 'individual',
        public ?string $taxNumber = null,
        public ?float $creditLimit = null
    ) {
    }
}
