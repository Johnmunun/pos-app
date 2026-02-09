<?php

namespace Src\Domain\Currency\Entities;

use Src\Domain\Currency\ValueObjects\CurrencyCode;
use Src\Domain\Currency\ValueObjects\CurrencyName;
use Src\Domain\Currency\ValueObjects\CurrencySymbol;

/**
 * Entity: Currency
 * 
 * Représente une devise dans le système
 */
class Currency
{
    private int $id;
    private int $tenantId;
    private CurrencyCode $code;
    private CurrencyName $name;
    private CurrencySymbol $symbol;
    private bool $isDefault;
    private bool $isActive;

    public function __construct(
        int $id,
        int $tenantId,
        CurrencyCode $code,
        CurrencyName $name,
        CurrencySymbol $symbol,
        bool $isDefault = false,
        bool $isActive = true
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->code = $code;
        $this->name = $name;
        $this->symbol = $symbol;
        $this->isDefault = $isDefault;
        $this->isActive = $isActive;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getCode(): CurrencyCode
    {
        return $this->code;
    }

    public function getName(): CurrencyName
    {
        return $this->name;
    }

    public function getSymbol(): CurrencySymbol
    {
        return $this->symbol;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setAsDefault(): void
    {
        $this->isDefault = true;
    }

    public function unsetAsDefault(): void
    {
        $this->isDefault = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function update(
        CurrencyCode $code = null,
        CurrencyName $name = null,
        CurrencySymbol $symbol = null
    ): void {
        if ($code !== null) {
            $this->code = $code;
        }
        if ($name !== null) {
            $this->name = $name;
        }
        if ($symbol !== null) {
            $this->symbol = $symbol;
        }
    }
}
