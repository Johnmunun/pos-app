<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Src\Domains\Admin\Repositories\AdminRepositoryInterface;
use Src\Infrastructure\Admin\Repositories\AdminEloquentRepository;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
