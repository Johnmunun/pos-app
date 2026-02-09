<?php

namespace Src\Infrastructure\Settings\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Settings\Repositories\StoreSettingsRepositoryInterface;
use Src\Infrastructure\Settings\Persistence\EloquentStoreSettingsRepository;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Application\Settings\UseCases\UpdateStoreSettingsUseCase;
use Src\Infrastructure\Settings\Services\StoreLogoService;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository
        $this->app->bind(
            StoreSettingsRepositoryInterface::class,
            EloquentStoreSettingsRepository::class
        );

        // Services
        $this->app->singleton(StoreLogoService::class, function () {
            return new StoreLogoService();
        });

        // Use Cases
        $this->app->bind(
            GetStoreSettingsUseCase::class,
            function ($app) {
                return new GetStoreSettingsUseCase(
                    $app->make(StoreSettingsRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            UpdateStoreSettingsUseCase::class,
            function ($app) {
                return new UpdateStoreSettingsUseCase(
                    $app->make(StoreSettingsRepositoryInterface::class)
                );
            }
        );
    }
}
