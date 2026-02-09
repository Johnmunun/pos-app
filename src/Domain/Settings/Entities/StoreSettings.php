<?php

namespace Src\Domain\Settings\Entities;

use DateTimeImmutable;
use Src\Domain\Settings\ValueObjects\CompanyIdentity;
use Src\Domain\Settings\ValueObjects\Address;

/**
 * Entity: StoreSettings
 * 
 * Représente les paramètres d'une boutique
 */
class StoreSettings
{
    private string $id;
    private string $shopId;
    private CompanyIdentity $companyIdentity;
    private Address $address;
    private ?string $phone;
    private ?string $email;
    private ?string $logoPath;
    private string $currency;
    private ?float $exchangeRate;
    private ?string $invoiceFooterText;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $shopId,
        CompanyIdentity $companyIdentity,
        Address $address,
        ?string $phone = null,
        ?string $email = null,
        ?string $logoPath = null,
        string $currency = 'XAF',
        ?float $exchangeRate = null,
        ?string $invoiceFooterText = null
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->companyIdentity = $companyIdentity;
        $this->address = $address;
        $this->phone = $phone;
        $this->email = $email;
        $this->logoPath = $logoPath;
        $this->currency = $currency;
        $this->exchangeRate = $exchangeRate;
        $this->invoiceFooterText = $invoiceFooterText;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getCompanyIdentity(): CompanyIdentity
    {
        return $this->companyIdentity;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getExchangeRate(): ?float
    {
        return $this->exchangeRate;
    }

    public function getInvoiceFooterText(): ?string
    {
        return $this->invoiceFooterText;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Business methods
    public function updateCompanyIdentity(CompanyIdentity $companyIdentity): void
    {
        $this->companyIdentity = $companyIdentity;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateAddress(Address $address): void
    {
        $this->address = $address;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updatePhone(?string $phone): void
    {
        $this->phone = $phone;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateEmail(?string $email): void
    {
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }
        $this->email = $email;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateLogo(?string $logoPath): void
    {
        $this->logoPath = $logoPath;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateCurrency(string $currency): void
    {
        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Le code devise doit contenir 3 caractères');
        }
        $this->currency = strtoupper($currency);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateExchangeRate(?float $exchangeRate): void
    {
        if ($exchangeRate !== null && $exchangeRate <= 0) {
            throw new \InvalidArgumentException('Le taux de change doit être positif');
        }
        $this->exchangeRate = $exchangeRate;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateInvoiceFooterText(?string $text): void
    {
        $this->invoiceFooterText = $text;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isComplete(): bool
    {
        return !empty($this->companyIdentity->getName())
            && !empty($this->address->getStreet())
            && !empty($this->phone)
            && !empty($this->email);
    }

    // Static factory method
    public static function create(
        string $shopId,
        CompanyIdentity $companyIdentity,
        Address $address,
        ?string $phone = null,
        ?string $email = null,
        ?string $logoPath = null,
        string $currency = 'XAF',
        ?float $exchangeRate = null,
        ?string $invoiceFooterText = null
    ): self {
        return new self(
            \Ramsey\Uuid\Uuid::uuid4()->toString(),
            $shopId,
            $companyIdentity,
            $address,
            $phone,
            $email,
            $logoPath,
            $currency,
            $exchangeRate,
            $invoiceFooterText
        );
    }
}
