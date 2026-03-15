<?php

declare(strict_types=1);

namespace Src\Domain\ModuleOnboarding\Repositories;

use Src\Domain\ModuleOnboarding\Entities\ModuleOnboardingStatus;

interface ModuleOnboardingStatusRepositoryInterface
{
    public function findByUserAndModule(int $userId, string $moduleName): ?ModuleOnboardingStatus;

    public function save(ModuleOnboardingStatus $status): void;
}
