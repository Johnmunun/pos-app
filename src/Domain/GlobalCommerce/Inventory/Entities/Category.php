<?php

namespace Src\Domain\GlobalCommerce\Inventory\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Catégorie générique GlobalCommerce (hiérarchique).
 */
class Category
{
    private string $id;
    private string $shopId;
    private string $name;
    private string $description;
    private ?string $parentId;
    private int $sortOrder;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $shopId,
        string $name,
        ?string $description = null,
        ?string $parentId = null,
        int $sortOrder = 0,
        bool $isActive = true
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->name = $name;
        $this->description = $description ?? '';
        $this->parentId = $parentId;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getShopId(): string { return $this->shopId; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getParentId(): ?string { return $this->parentId; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function rename(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function changeDescription(string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function setParent(?string $parentId): void
    {
        $this->parentId = $parentId;
        $this->touch();
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
        $this->touch();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->touch();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $shopId,
        string $name,
        ?string $description = null,
        ?string $parentId = null,
        int $sortOrder = 0
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $shopId,
            $name,
            $description,
            $parentId,
            $sortOrder,
            true
        );
    }
}

