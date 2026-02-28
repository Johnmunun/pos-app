<?php

declare(strict_types=1);

namespace Src\Domain\Quincaillerie\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Domain\Quincaillerie\ValueObjects\CustomerType;
use Src\Domain\Quincaillerie\ValueObjects\SupplierEmail;
use Src\Domain\Quincaillerie\ValueObjects\SupplierPhone;
use InvalidArgumentException;

/**
 * Entity: Customer
 *
 * Représente un client dans le module Quincaillerie.
 */
final class Customer
{
    private string $id;
    private int $shopId;
    private string $name;
    private SupplierPhone $phone;
    private SupplierEmail $email;
    private ?string $address;
    private CustomerType $customerType;
    private ?string $taxNumber;
    private ?float $creditLimit;
    private string $status;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    private function __construct(
        string $id,
        int $shopId,
        string $name,
        SupplierPhone $phone,
        SupplierEmail $email,
        ?string $address,
        CustomerType $customerType,
        ?string $taxNumber,
        ?float $creditLimit,
        string $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->name = $name;
        $this->phone = $phone;
        $this->email = $email;
        $this->address = $address;
        $this->customerType = $customerType;
        $this->taxNumber = $taxNumber;
        $this->creditLimit = $creditLimit;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Factory method pour créer un nouveau client.
     */
    public static function create(
        int $shopId,
        string $name,
        SupplierPhone $phone,
        SupplierEmail $email,
        ?string $address = null,
        ?CustomerType $customerType = null,
        ?string $taxNumber = null,
        ?float $creditLimit = null
    ): self {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('Le nom du client est obligatoire.');
        }

        if ($creditLimit !== null && $creditLimit < 0) {
            throw new InvalidArgumentException('La limite de crédit ne peut pas être négative.');
        }

        $now = new DateTimeImmutable();

        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            trim($name),
            $phone,
            $email,
            $address !== null ? trim($address) : null,
            $customerType ?? CustomerType::individual(),
            $taxNumber !== null ? trim($taxNumber) : null,
            $creditLimit !== null ? round($creditLimit, 2) : null,
            'active',
            $now,
            $now
        );
    }

    /**
     * Factory method pour reconstituer depuis la persistence.
     */
    public static function reconstitute(
        string $id,
        int $shopId,
        string $name,
        SupplierPhone $phone,
        SupplierEmail $email,
        ?string $address,
        CustomerType $customerType,
        ?string $taxNumber,
        ?float $creditLimit,
        string $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            $id,
            $shopId,
            $name,
            $phone,
            $email,
            $address,
            $customerType,
            $taxNumber,
            $creditLimit,
            $status,
            $createdAt,
            $updatedAt
        );
    }

    /**
     * Met à jour les informations du client.
     */
    public function update(
        ?string $name = null,
        ?SupplierPhone $phone = null,
        ?SupplierEmail $email = null,
        ?string $address = null,
        ?CustomerType $customerType = null,
        ?string $taxNumber = null,
        ?float $creditLimit = null
    ): void {
        if ($name !== null) {
            $trimmedName = trim($name);
            if (empty($trimmedName)) {
                throw new InvalidArgumentException('Le nom du client est obligatoire.');
            }
            $this->name = $trimmedName;
        }

        if ($phone !== null) {
            $this->phone = $phone;
        }

        if ($email !== null) {
            $this->email = $email;
        }

        if ($address !== null) {
            $this->address = trim($address) ?: null;
        }

        if ($customerType !== null) {
            $this->customerType = $customerType;
        }

        if ($taxNumber !== null) {
            $this->taxNumber = trim($taxNumber) ?: null;
        }

        if ($creditLimit !== null) {
            if ($creditLimit < 0) {
                throw new InvalidArgumentException('La limite de crédit ne peut pas être négative.');
            }
            $this->creditLimit = round($creditLimit, 2);
        }

        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Met à jour la limite de crédit.
     */
    public function setCreditLimit(?float $creditLimit): void
    {
        if ($creditLimit !== null && $creditLimit < 0) {
            throw new InvalidArgumentException('La limite de crédit ne peut pas être négative.');
        }

        $this->creditLimit = $creditLimit !== null ? round($creditLimit, 2) : null;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Active le client.
     */
    public function activate(): void
    {
        if ($this->status === 'active') {
            return;
        }

        $this->status = 'active';
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Désactive le client.
     */
    public function deactivate(): void
    {
        if ($this->status === 'inactive') {
            return;
        }

        $this->status = 'inactive';
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Vérifie si le client peut bénéficier d'un crédit.
     */
    public function canHaveCredit(): bool
    {
        return $this->creditLimit !== null && $this->creditLimit > 0;
    }

    // Getters

    public function getId(): string
    {
        return $this->id;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPhone(): SupplierPhone
    {
        return $this->phone;
    }

    public function getEmail(): SupplierEmail
    {
        return $this->email;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCustomerType(): CustomerType
    {
        return $this->customerType;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function getCreditLimit(): ?float
    {
        return $this->creditLimit;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompany(): bool
    {
        return $this->customerType->isCompany();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
