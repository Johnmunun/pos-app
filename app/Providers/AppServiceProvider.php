<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Src\Domains\Admin\Repositories\AdminRepositoryInterface;
use Src\Infrastructure\Admin\Repositories\AdminEloquentRepository;
use Src\Domain\ModuleOnboarding\Repositories\ModuleOnboardingStatusRepositoryInterface;
use Src\Infrastructure\ModuleOnboarding\Persistence\EloquentModuleOnboardingStatusRepository;
use Src\Application\ModuleOnboarding\ModuleOnboardingService;
use App\Services\PermissionSyncService;
use Src\Domains\User\Services\PermissionsSyncService as DomainPermissionsSyncService;
use Src\Domains\StoreProvisioning\Contracts\StoreTemplateProvisionerInterface;
use Src\Infrastructure\StoreProvisioning\StoreTemplateProvisioner;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the AdminRepositoryInterface to its implementation
        $this->app->bind(
            AdminRepositoryInterface::class,
            AdminEloquentRepository::class
        );

        $this->app->bind(
            ModuleOnboardingStatusRepositoryInterface::class,
            EloquentModuleOnboardingStatusRepository::class
        );
        $this->app->bind(ModuleOnboardingService::class, function ($app) {
            return new ModuleOnboardingService(
                $app->make(ModuleOnboardingStatusRepositoryInterface::class)
            );
        });

        // Bind DomainPermissionsSyncService
        $this->app->singleton(DomainPermissionsSyncService::class);

        // Bind PermissionSyncService wrapper
        $this->app->bind(
            PermissionSyncService::class,
            function ($app) {
                return new PermissionSyncService(
                    $app->make(DomainPermissionsSyncService::class)
                );
            }
        );

        $this->app->singleton(StoreTemplateProvisionerInterface::class, StoreTemplateProvisioner::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
