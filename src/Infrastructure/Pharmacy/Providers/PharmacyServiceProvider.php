<?php

namespace Src\Infrastructure\Pharmacy\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\BatchRepositoryInterface;
use Src\Domain\Pharmacy\Services\InventoryService;
use Src\Domain\Pharmacy\Services\ExpiryAlertService;
use Src\Infrastructure\Pharmacy\Persistence\EloquentProductRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentCategoryRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentBatchRepository;
use Src\Application\Pharmacy\UseCases\Product\CreateProductUseCase;
use Src\Application\Pharmacy\UseCases\Product\UpdateProductUseCase;
use Src\Application\Pharmacy\UseCases\Inventory\UpdateStockUseCase;
use Src\Application\Pharmacy\UseCases\Category\CreateCategoryUseCase;
use Src\Application\Pharmacy\UseCases\Category\UpdateCategoryUseCase;
use Src\Application\Pharmacy\UseCases\Category\DeleteCategoryUseCase;
use Src\Application\Pharmacy\Services\DashboardService;

class PharmacyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Domain Services
        $this->app->bind(InventoryService::class, function () {
            return new InventoryService();
        });
        
        $this->app->bind(ExpiryAlertService::class, function () {
            return new ExpiryAlertService();
        });

        // Repositories
        $this->app->bind(
            ProductRepositoryInterface::class,
            EloquentProductRepository::class
        );
        
        $this->app->bind(
            CategoryRepositoryInterface::class,
            EloquentCategoryRepository::class
        );
        
        $this->app->bind(
            BatchRepositoryInterface::class,
            EloquentBatchRepository::class
        );

        // Application Services
        $this->app->bind(
            DashboardService::class,
            function ($app) {
                return new DashboardService(
                    $app->make(ProductRepositoryInterface::class),
                    $app->make(BatchRepositoryInterface::class),
                    $app->make(InventoryService::class),
                    $app->make(ExpiryAlertService::class)
                );
            }
        );

        // Use Cases
        $this->app->bind(
            CreateProductUseCase::class,
            function ($app) {
                return new CreateProductUseCase(
                    $app->make(ProductRepositoryInterface::class),
                    $app->make(CategoryRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            UpdateProductUseCase::class,
            function ($app) {
                return new UpdateProductUseCase(
                    $app->make(ProductRepositoryInterface::class),
                    $app->make(CategoryRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            UpdateStockUseCase::class,
            function ($app) {
                return new UpdateStockUseCase(
                    $app->make(ProductRepositoryInterface::class),
                    $app->make(BatchRepositoryInterface::class)
                );
            }
        );

        // Category Use Cases
        $this->app->bind(
            CreateCategoryUseCase::class,
            function ($app) {
                return new CreateCategoryUseCase(
                    $app->make(CategoryRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            UpdateCategoryUseCase::class,
            function ($app) {
                return new UpdateCategoryUseCase(
                    $app->make(CategoryRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            DeleteCategoryUseCase::class,
            function ($app) {
                return new DeleteCategoryUseCase(
                    $app->make(CategoryRepositoryInterface::class)
                );
            }
        );
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}