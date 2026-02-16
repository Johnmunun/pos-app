<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Domain\Pharmacy\ValueObjects\SupplierStatus;
use Src\Domain\Pharmacy\ValueObjects\SupplierEmail;
use Src\Domain\Pharmacy\ValueObjects\SupplierPhone;

/**
 * Entity: Supplier
 *
 * Représente un fournisseur dans le module Pharmacy.
 */
final class Supplier
{
    private string $id;
    private int $shopId;
    private string $name;
    private ?string $contactPerson;
    private SupplierPhone $phone;
    private SupplierEmail $email;
    private ?string $address;
    private SupplierStatus $status;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    private function __construct(
        string $id,
        int $shopId,
        string $name,
        ?string $contactPerson,
        SupplierPhone $phone,
        SupplierEmail $email,
        ?string $address,
        SupplierStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->name = $name;
        $this->contactPerson = $contactPerson;
        $this->phone = $phone;
        $this->email = $email;
        $this->address = $address;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Factory method pour créer un nouveau fournisseur.
     */
    public static function create(
        int $shopId,
        string $name,
        ?string $contactPerson,
        SupplierPhone $phone,
        SupplierEmail $email,
        ?string $address
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            trim($name),
            $contactPerson !== null ? trim($contactPerson) : null,
            $phone,
            $email,
            $address !== null ? trim($address) : null,
            SupplierStatus::active(),
            $now,
            $now
        );
    }

    /**
     * Factory method pour reconstituer un fournisseur depuis la persistence.
     */
    public static function reconstitute(
        string $id,
        int $shopId,
        string $name,
        ?string $contactPerson,
        SupplierPhone $phone,
        SupplierEmail $email,
        ?string $address,
        SupplierStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            $id,
            $shopId,
            $name,
            $contactPerson,
            $phone,
            $email,
            $address,
            $status,
            $createdAt,
            $updatedAt
        );
    }

    /**
     * Met à jour les informations du fournisseur.
     */
    public function update(
        ?string $name,
        ?string $contactPerson,
        ?SupplierPhone $phone,
        ?SupplierEmail $email,
        ?string $address
    ): void {
        if ($name !== null) {
            $this->name = trim($name);
        }

        if ($contactPerson !== null) {
            $this->contactPerson = trim($contactPerson) ?: null;
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

        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Active le fournisseur.
     */
    public function activate(): void
    {
        if ($this->status->isActive()) {
            return;
        }

        $this->status = SupplierStatus::active();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Désactive le fournisseur.
     */
    public function deactivate(): void
    {
        if ($this->status->isInactive()) {
            return;
        }

        $this->status = SupplierStatus::inactive();
        $this->updatedAt = new DateTimeImmutable();
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

    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
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

    public function getStatus(): SupplierStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
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
