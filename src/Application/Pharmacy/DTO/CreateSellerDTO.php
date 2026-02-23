<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\DTO;

/**
 * DTO: CreateSellerDTO
 *
 * Data Transfer Object pour créer un vendeur
 *
 * @phpstan-type RoleIdArray array<int>
 */
final class CreateSellerDTO
{
    /**
     * @param int $tenantId
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $password
     * @param RoleIdArray|null $roleIds
     * @param bool $isActive
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $password,
        public readonly ?array $roleIds = null,
        public readonly bool $isActive = true
    ) {
    }
}
