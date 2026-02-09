<?php

namespace Src\Application\Currency\DTO;

/**
 * DTO: UpdateCurrencyDTO
 * 
 * Data Transfer Object pour mettre à jour une devise
 */
class UpdateCurrencyDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $code = null,
        public readonly ?string $name = null,
        public readonly ?string $symbol = null,
        public readonly ?bool $isDefault = null
    ) {}
}
