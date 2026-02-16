<?php

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Shared\ValueObjects\Money;
use Src\Shared\ValueObjects\Quantity;

/**
 * Entité de domaine : SaleLine
 *
 * Ligne d'une vente : un produit, une quantité, un prix unitaire, un total.
 */
class SaleLine
{
    private string $id;
    private string $saleId;
    private string $productId;
    private Quantity $quantity;
    private Money $unitPrice;
    private Money $lineTotal;
    private ?float $discountPercent;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $saleId,
        string $productId,
        Quantity $quantity,
        Money $unitPrice,
        Money $lineTotal,
        ?float $discountPercent,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->saleId = $saleId;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->lineTotal = $lineTotal;
        $this->discountPercent = $discountPercent;
        $this->createdAt = $createdAt;
    }

    public static function create(
        string $saleId,
        string $productId,
        Quantity $quantity,
        Money $unitPrice,
        ?float $discountPercent = null
    ): self {
        if ($discountPercent !== null && ($discountPercent < 0 || $discountPercent > 100)) {
            throw new \InvalidArgumentException('Discount percent must be between 0 and 100');
        }

        $lineTotal = $unitPrice->multiply($quantity->getValue());

        if ($discountPercent !== null && $discountPercent > 0) {
            $discountAmount = $lineTotal->multiply($discountPercent / 100);
            $lineTotal = $lineTotal->subtract($discountAmount);
        }

        return new self(
            Uuid::uuid4()->toString(),
            $saleId,
            $productId,
            $quantity,
            $unitPrice,
            $lineTotal,
            $discountPercent,
            new DateTimeImmutable()
        );
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getSaleId(): string { return $this->saleId; }
    public function getProductId(): string { return $this->productId; }
    public function getQuantity(): Quantity { return $this->quantity; }
    public function getUnitPrice(): Money { return $this->unitPrice; }
    public function getLineTotal(): Money { return $this->lineTotal; }
    public function getDiscountPercent(): ?float { return $this->discountPercent; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }

    public function changeQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
        $this->recalculateTotal();
    }

    public function changeUnitPrice(Money $unitPrice): void
    {
        if ($unitPrice->getCurrency() !== $this->unitPrice->getCurrency()) {
            throw new \InvalidArgumentException('Currency mismatch for unit price');
        }

        $this->unitPrice = $unitPrice;
        $this->recalculateTotal();
    }

    public function changeDiscount(?float $discountPercent): void
    {
        if ($discountPercent !== null && ($discountPercent < 0 || $discountPercent > 100)) {
            throw new \InvalidArgumentException('Discount percent must be between 0 and 100');
        }

        $this->discountPercent = $discountPercent;
        $this->recalculateTotal();
    }

    private function recalculateTotal(): void
    {
        $lineTotal = $this->unitPrice->multiply($this->quantity->getValue());

        if ($this->discountPercent !== null && $this->discountPercent > 0) {
            $discountAmount = $lineTotal->multiply($this->discountPercent / 100);
            $lineTotal = $lineTotal->subtract($discountAmount);
        }

        $this->lineTotal = $lineTotal;
    }
}

