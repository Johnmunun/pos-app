<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Src\Domains\Admin\Repositories\AdminRepositoryInterface;
use Src\Infrastructure\Admin\Repositories\AdminEloquentRepository;
use App\Services\PermissionSyncService;
use Src\Domains\User\Services\PermissionsSyncService as DomainPermissionsSyncService;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
