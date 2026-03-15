<?php

namespace Src\Infrastructure\ModuleOnboarding\Persistence;

use Src\Domain\ModuleOnboarding\Entities\ModuleOnboardingStatus;
use Src\Domain\ModuleOnboarding\Repositories\ModuleOnboardingStatusRepositoryInterface;
use Src\Infrastructure\ModuleOnboarding\Models\ModuleOnboardingStatusModel;

class EloquentModuleOnboardingStatusRepository implements ModuleOnboardingStatusRepositoryInterface
{
    public function findByUserAndModule(int $userId, string $moduleName): ?ModuleOnboardingStatus
    {
        $model = ModuleOnboardingStatusModel::query()
            ->where('user_id', $userId)
            ->where('module_name', $moduleName)
            ->first();

        if (!$model) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function save(ModuleOnboardingStatus $status): void
    {
        $data = [
            'user_id' => $status->getUserId(),
            'module_name' => $status->getModuleName(),
            'steps_completed' => $status->getStepsCompleted(),
            'status' => $status->getStatus(),
            'updated_at' => $status->getUpdatedAt(),
        ];

        if ($status->getId() !== null) {
            ModuleOnboardingStatusModel::where('id', $status->getId())->update($data);
            return;
        }

        $model = new ModuleOnboardingStatusModel();
        $model->fill($data);
        $model->save();
    }

    private function toEntity(ModuleOnboardingStatusModel $model): ModuleOnboardingStatus
    {
        return new ModuleOnboardingStatus(
            $model->id,
            (int) $model->user_id,
            $model->module_name,
            $model->steps_completed ?? [],
            (int) $model->status,
            $model->updated_at->toDateTimeImmutable()
        );
    }
}
