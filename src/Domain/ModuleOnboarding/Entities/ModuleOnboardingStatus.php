<?php

declare(strict_types=1);

namespace Src\Domain\ModuleOnboarding\Entities;

use DateTimeImmutable;

/**
 * État d'avancement de l'onboarding d'un utilisateur pour un module.
 * status: 0 = en cours, 1 = terminé
 */
final class ModuleOnboardingStatus
{
    public const STATUS_IN_PROGRESS = 0;
    public const STATUS_COMPLETED = 1;

    private ?int $id;
    private int $userId;
    private string $moduleName;
    /** @var list<string> étapes complétées (identifiants) */
    private array $stepsCompleted;
    private int $status;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        ?int $id,
        int $userId,
        string $moduleName,
        array $stepsCompleted,
        int $status,
        DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->moduleName = $moduleName;
        $this->stepsCompleted = array_values($stepsCompleted);
        $this->status = $status;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /** @return list<string> */
    public function getStepsCompleted(): array
    {
        return $this->stepsCompleted;
    }

    public function isStepCompleted(string $stepId): bool
    {
        return in_array($stepId, $this->stepsCompleted, true);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withStepCompleted(string $stepId): self
    {
        if ($this->isStepCompleted($stepId)) {
            return $this;
        }
        $steps = $this->stepsCompleted;
        $steps[] = $stepId;
        return new self(
            $this->id,
            $this->userId,
            $this->moduleName,
            $steps,
            $this->status,
            new DateTimeImmutable()
        );
    }

    public function withCompleted(): self
    {
        return new self(
            $this->id,
            $this->userId,
            $this->moduleName,
            $this->stepsCompleted,
            self::STATUS_COMPLETED,
            new DateTimeImmutable()
        );
    }
}
