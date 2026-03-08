<?php

namespace Src\Domain\Ecommerce\Entities;

use DateTimeImmutable;
use Src\Shared\ValueObjects\Money;

/**
 * Entité de domaine : Customer
 *
 * Représente un client ecommerce.
 */
class Customer
{
    private string $id;
    private string $shopId;
    private string $email;
    private string $firstName;
    private string $lastName;
    private ?string $phone;
    private ?string $defaultShippingAddress;
    private ?string $defaultBillingAddress;
    private int $totalOrders;
    private Money $totalSpent;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $shopId,
        string $email,
        string $firstName,
        string $lastName,
        ?string $phone,
        ?string $defaultShippingAddress,
        ?string $defaultBillingAddress,
        int $totalOrders,
        Money $totalSpent,
        bool $isActive,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->defaultShippingAddress = $defaultShippingAddress;
        $this->defaultBillingAddress = $defaultBillingAddress;
        $this->totalOrders = $totalOrders;
        $this->totalSpent = $totalSpent;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getEmail(): string { return $this->email; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getFullName(): string { return $this->firstName . ' ' . $this->lastName; }
    public function getPhone(): ?string { return $this->phone; }
    public function getDefaultShippingAddress(): ?string { return $this->defaultShippingAddress; }
    public function getDefaultBillingAddress(): ?string { return $this->defaultBillingAddress; }
    public function getTotalOrders(): int { return $this->totalOrders; }
    public function getTotalSpent(): Money { return $this->totalSpent; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    // Méthodes métier
    public function updatePersonalInfo(string $firstName, string $lastName, ?string $phone): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateAddresses(?string $shippingAddress, ?string $billingAddress): void
    {
        $this->defaultShippingAddress = $shippingAddress;
        $this->defaultBillingAddress = $billingAddress;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function incrementOrderCount(Money $orderAmount): void
    {
        if ($orderAmount->getCurrency() !== $this->totalSpent->getCurrency()) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        $this->totalOrders++;
        $this->totalSpent = $this->totalSpent->add($orderAmount);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }
}
