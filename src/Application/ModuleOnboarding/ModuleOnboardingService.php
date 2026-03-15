<?php

declare(strict_types=1);

namespace Src\Application\ModuleOnboarding;

use Src\Domain\ModuleOnboarding\Entities\ModuleOnboardingStatus;
use Src\Domain\ModuleOnboarding\Repositories\ModuleOnboardingStatusRepositoryInterface;

final class ModuleOnboardingService
{
    public function __construct(
        private ModuleOnboardingStatusRepositoryInterface $repository
    ) {
    }

    public function getStatus(int $userId, string $moduleName): ModuleOnboardingStatus
    {
        $status = $this->repository->findByUserAndModule($userId, $moduleName);
        if ($status !== null) {
            return $status;
        }
        return new ModuleOnboardingStatus(
            null,
            $userId,
            $moduleName,
            [],
            0,
            new \DateTimeImmutable()
        );
    }

    public function completeStep(int $userId, string $moduleName, string $stepId): ModuleOnboardingStatus
    {
        $status = $this->getStatus($userId, $moduleName);
        if ($status->isCompleted()) {
            return $status;
        }
        $status = $status->withStepCompleted($stepId);
        $this->repository->save($status);
        return $status;
    }

    public function completeModule(int $userId, string $moduleName): ModuleOnboardingStatus
    {
        $status = $this->getStatus($userId, $moduleName);
        $status = $status->withCompleted();
        $this->repository->save($status);
        return $status;
    }
}
