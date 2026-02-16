<?php

declare(strict_types=1);

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Src\Domain\Pharmacy\ValueObjects\TaxRate;

/**
 * Entity: SupplierProductPrice
 *
 * Représente le prix d'un produit chez un fournisseur.
 */
final class SupplierProductPrice
{
    private string $id;
    private string $supplierId;
    private string $productId;
    private float $normalPrice;
    private ?float $agreedPrice;
    private TaxRate $taxRate;
    private DateTimeImmutable $effectiveFrom;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    private function __construct(
        string $id,
        string $supplierId,
        string $productId,
        float $normalPrice,
        ?float $agreedPrice,
        TaxRate $taxRate,
        DateTimeImmutable $effectiveFrom,
        bool $isActive,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->supplierId = $supplierId;
        $this->productId = $productId;
        $this->normalPrice = $normalPrice;
        $this->agreedPrice = $agreedPrice;
        $this->taxRate = $taxRate;
        $this->effectiveFrom = $effectiveFrom;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Factory method pour créer un nouveau prix fournisseur-produit.
     */
    public static function create(
        string $supplierId,
        string $productId,
        float $normalPrice,
        ?float $agreedPrice = null,
        ?TaxRate $taxRate = null,
        ?DateTimeImmutable $effectiveFrom = null
    ): self {
        if ($normalPrice < 0) {
            throw new \InvalidArgumentException('Le prix normal ne peut pas être négatif.');
        }

        if ($agreedPrice !== null && $agreedPrice < 0) {
            throw new \InvalidArgumentException('Le prix convenu ne peut pas être négatif.');
        }

        $now = new DateTimeImmutable();

        return new self(
            Uuid::uuid4()->toString(),
            $supplierId,
            $productId,
            round($normalPrice, 2),
            $agreedPrice !== null ? round($agreedPrice, 2) : null,
            $taxRate ?? TaxRate::zero(),
            $effectiveFrom ?? $now,
            true,
            $now,
            $now
        );
    }

    /**
     * Factory method pour reconstituer depuis la persistence.
     */
    public static function reconstitute(
        string $id,
        string $supplierId,
        string $productId,
        float $normalPrice,
        ?float $agreedPrice,
        TaxRate $taxRate,
        DateTimeImmutable $effectiveFrom,
        bool $isActive,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            $id,
            $supplierId,
            $productId,
            $normalPrice,
            $agreedPrice,
            $taxRate,
            $effectiveFrom,
            $isActive,
            $createdAt,
            $updatedAt
        );
    }

    /**
     * Met à jour les prix.
     */
    public function updatePrices(
        ?float $normalPrice = null,
        ?float $agreedPrice = null,
        ?TaxRate $taxRate = null,
        ?DateTimeImmutable $effectiveFrom = null
    ): void {
        if ($normalPrice !== null) {
            if ($normalPrice < 0) {
                throw new \InvalidArgumentException('Le prix normal ne peut pas être négatif.');
            }
            $this->normalPrice = round($normalPrice, 2);
        }

        // Pour agreedPrice, null signifie "ne pas changer", 0 signifie "supprimer"
        if ($agreedPrice !== null) {
            if ($agreedPrice < 0) {
                throw new \InvalidArgumentException('Le prix convenu ne peut pas être négatif.');
            }
            $this->agreedPrice = $agreedPrice > 0 ? round($agreedPrice, 2) : null;
        }

        if ($taxRate !== null) {
            $this->taxRate = $taxRate;
        }

        if ($effectiveFrom !== null) {
            $this->effectiveFrom = $effectiveFrom;
        }

        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Désactive ce prix (pour historisation).
     */
    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Réactive ce prix.
     */
    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Retourne le prix effectif (agreedPrice si défini, sinon normalPrice).
     */
    public function getEffectivePrice(): float
    {
        return $this->agreedPrice ?? $this->normalPrice;
    }

    /**
     * Retourne le prix TTC.
     */
    public function getEffectivePriceWithTax(): float
    {
        return $this->taxRate->calculateTaxIncluded($this->getEffectivePrice());
    }

    /**
     * Retourne le montant de taxe sur le prix effectif.
     */
    public function getTaxAmount(): float
    {
        return $this->taxRate->calculateTaxAmount($this->getEffectivePrice());
    }

    // Getters

    public function getId(): string
    {
        return $this->id;
    }

    public function getSupplierId(): string
    {
        return $this->supplierId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getNormalPrice(): float
    {
        return $this->normalPrice;
    }

    public function getAgreedPrice(): ?float
    {
        return $this->agreedPrice;
    }

    public function hasAgreedPrice(): bool
    {
        return $this->agreedPrice !== null;
    }

    public function getTaxRate(): TaxRate
    {
        return $this->taxRate;
    }

    public function getEffectiveFrom(): DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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
