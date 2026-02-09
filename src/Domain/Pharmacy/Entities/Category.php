<?php

namespace Src\Domain\Pharmacy\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

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
        int $sortOrder = 0
    ) {
        $this->id = $id;
        $this->shopId = $shopId;
        $this->name = $name;
        $this->description = $description ?? '';
        $this->parentId = $parentId;
        $this->sortOrder = $sortOrder;
        $this->isActive = true;
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

    // Business methods
    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateDescription(string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setParentId(?string $parentId): void
    {
        $this->parentId = $parentId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function hasParent(): bool
    {
        return $this->parentId !== null;
    }

    // Static factory method
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
            $sortOrder
        );
    }
}