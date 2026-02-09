<?php

namespace Src\Application\Currency\DTO;

/**
 * DTO: CreateCurrencyDTO
 * 
 * Data Transfer Object pour créer une devise
 */
class CreateCurrencyDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $symbol,
        public readonly bool $isDefault = false
    ) {}
}
