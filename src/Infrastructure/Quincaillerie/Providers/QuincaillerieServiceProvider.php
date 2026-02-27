<?php

namespace Src\Infrastructure\Quincaillerie\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface;
use Src\Domain\Quincaillerie\Repositories\CategoryRepositoryInterface;
use Src\Infrastructure\Quincaillerie\Persistence\EloquentProductRepository;
use Src\Infrastructure\Quincaillerie\Persistence\EloquentCategoryRepository;
use Src\Application\Quincaillerie\UseCases\Product\CreateProductUseCase;
use Src\Application\Quincaillerie\UseCases\Product\UpdateProductUseCase;
use Src\Application\Quincaillerie\UseCases\Product\GenerateProductCodeUseCase;
use Src\Application\Quincaillerie\UseCases\Category\CreateCategoryUseCase;
use Src\Application\Quincaillerie\UseCases\Category\UpdateCategoryUseCase;
use Src\Application\Quincaillerie\UseCases\Category\DeleteCategoryUseCase;

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
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}
