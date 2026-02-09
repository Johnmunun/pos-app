<?php

namespace Src\Domains\Onboarding\Entities;

use Src\Domains\Onboarding\ValueObjects\BusinessType;
use Src\Domains\Onboarding\ValueObjects\Sector;

/**
 * Entité OnboardingSession
 * 
 * Représente une session d'onboarding en cours
 * Stocke les données intermédiaires de l'utilisateur
 */
class OnboardingSession
{
    private string $id;
    private array $stepData = [];
    private int $currentStep = 1;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    public function setStepData(int $step, array $data): void
    {
        $this->stepData[$step] = $data;
        $this->currentStep = max($this->currentStep, $step);
    }

    public function getStepData(int $step): array
    {
        return $this->stepData[$step] ?? [];
    }

    public function getAllData(): array
    {
        return $this->stepData;
    }

    public function isComplete(): bool
    {
        return $this->completedAt !== null;
    }

    public function markAsComplete(): void
    {
        $this->completedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }
}