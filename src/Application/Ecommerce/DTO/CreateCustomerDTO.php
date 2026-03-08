<?php

namespace Src\Application\Ecommerce\DTO;

/**
 * DTO: CreateCustomerDTO
 *
 * Data Transfer Object pour créer un client ecommerce.
 */
final class CreateCustomerDTO
{
    /**
     * @param string $shopId
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string|null $phone
     * @param string|null $defaultShippingAddress
     * @param string|null $defaultBillingAddress
     */
    public function __construct(
        public readonly string $shopId,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $phone,
        public readonly ?string $defaultShippingAddress,
        public readonly ?string $defaultBillingAddress
    ) {
    }
}
