<?php

namespace Src\Infrastructure\Quincaillerie\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface;
use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;
use Src\Domain\Quincaillerie\Repositories\SupplierRepositoryInterface;
use Src\Domain\Quincaillerie\Repositories\CustomerRepositoryInterface;
use Src\Infrastructure\Quincaillerie\Persistence\EloquentProductRepository;
use Src\Infrastructure\Quincaillerie\Persistence\EloquentCategoryRepository;
use Src\Infrastructure\Quincaillerie\Persistence\EloquentSupplierRepository;
use Src\Infrastructure\Quincaillerie\Persistence\EloquentCustomerRepository;
use Src\Application\Quincaillerie\UseCases\Product\CreateProductUseCase;
use Src\Application\Quincaillerie\UseCases\Product\UpdateProductUseCase;
use Src\Application\Quincaillerie\UseCases\Product\GenerateProductCodeUseCase;
use Src\Application\Quincaillerie\UseCases\Category\CreateCategoryUseCase;
use Src\Application\Quincaillerie\UseCases\Category\UpdateCategoryUseCase;
use Src\Application\Quincaillerie\UseCases\Category\DeleteCategoryUseCase;
use Src\Application\Quincaillerie\UseCases\Supplier\CreateSupplierUseCase;
use Src\Application\Quincaillerie\UseCases\Supplier\UpdateSupplierUseCase;
use Src\Application\Quincaillerie\UseCases\Supplier\ActivateSupplierUseCase;
use Src\Application\Quincaillerie\UseCases\Supplier\DeactivateSupplierUseCase;
use Src\Application\Quincaillerie\UseCases\Customer\CreateCustomerUseCase;
use Src\Application\Quincaillerie\UseCases\Customer\UpdateCustomerUseCase;
use Src\Application\Quincaillerie\UseCases\Customer\ActivateCustomerUseCase;
use Src\Application\Quincaillerie\UseCases\Customer\DeactivateCustomerUseCase;
use Src\Application\Quincaillerie\Services\DashboardService;
use Src\Application\Quincaillerie\Services\DepotFilterService;

/**
 * Service Provider du module Quincaillerie (DDD).
 * Enregistre les repositories et use cases. Aucune dépendance au module Pharmacy.
 */
class QuincaillerieServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            EloquentProductRepository::class
        );

        $this->app->bind(
            CategoryRepositoryInterface::class,
            EloquentCategoryRepository::class
        );

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
            GenerateProductCodeUseCase::class,
            function ($app) {
                return new GenerateProductCodeUseCase(
                    $app->make(ProductRepositoryInterface::class)
                );
            }
        );

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

        // Supplier Repository
        $this->app->bind(
            SupplierRepositoryInterface::class,
            EloquentSupplierRepository::class
        );

        // Supplier Use Cases
        $this->app->bind(
            CreateSupplierUseCase::class,
            function ($app) {
                return new CreateSupplierUseCase(
                    $app->make(SupplierRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            UpdateSupplierUseCase::class,
            function ($app) {
                return new UpdateSupplierUseCase(
                    $app->make(SupplierRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            ActivateSupplierUseCase::class,
            function ($app) {
                return new ActivateSupplierUseCase(
                    $app->make(SupplierRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            DeactivateSupplierUseCase::class,
            function ($app) {
                return new DeactivateSupplierUseCase(
                    $app->make(SupplierRepositoryInterface::class)
                );
            }
        );

        // Customer Repository
        $this->app->bind(
            CustomerRepositoryInterface::class,
            EloquentCustomerRepository::class
        );

        // Customer Use Cases
        $this->app->bind(
            CreateCustomerUseCase::class,
            function ($app) {
                return new CreateCustomerUseCase(
                    $app->make(CustomerRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            UpdateCustomerUseCase::class,
            function ($app) {
                return new UpdateCustomerUseCase(
                    $app->make(CustomerRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            ActivateCustomerUseCase::class,
            function ($app) {
                return new ActivateCustomerUseCase(
                    $app->make(CustomerRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            DeactivateCustomerUseCase::class,
            function ($app) {
                return new DeactivateCustomerUseCase(
                    $app->make(CustomerRepositoryInterface::class)
                );
            }
        );

        // Dashboard Service
        $this->app->bind(
            DashboardService::class,
            function ($app) {
                return new DashboardService(
                    $app->make(ProductRepositoryInterface::class),
                    $app->make(CategoryRepositoryInterface::class),
                    $app->make(SupplierRepositoryInterface::class),
                    $app->make(CustomerRepositoryInterface::class)
                );
            }
        );

        // Product Image Service
        $this->app->singleton(
            \Src\Infrastructure\Quincaillerie\Services\ProductImageService::class,
            \Src\Infrastructure\Quincaillerie\Services\ProductImageService::class
        );

        // Depot Filter Service
        $this->app->singleton(
            DepotFilterService::class,
            DepotFilterService::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}
