<?php

namespace Src\Infrastructure\GlobalCommerce\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\CategoryRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\StockTransferRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\StockTransferItemRepositoryInterface;
use Src\Domain\GlobalCommerce\Sales\Repositories\SaleRepositoryInterface;
use Src\Domain\GlobalCommerce\Procurement\Repositories\PurchaseRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Persistence\EloquentProductRepository;
use Src\Infrastructure\GlobalCommerce\Inventory\Persistence\EloquentCategoryRepository;
use Src\Infrastructure\GlobalCommerce\Inventory\Persistence\EloquentStockTransferRepository;
use Src\Infrastructure\GlobalCommerce\Inventory\Persistence\EloquentStockTransferItemRepository;
use Src\Infrastructure\GlobalCommerce\Sales\Persistence\EloquentSaleRepository;
use Src\Infrastructure\GlobalCommerce\Procurement\Persistence\EloquentPurchaseRepository;
use Src\Application\GlobalCommerce\Sales\UseCases\CreateSaleUseCase;
use Src\Application\GlobalCommerce\Procurement\UseCases\CreatePurchaseUseCase;
use Src\Application\GlobalCommerce\Procurement\UseCases\ReceivePurchaseUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\CreateProductUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\CreateCategoryUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\UpdateProductUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\UpdateCategoryUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\DeleteProductUseCase;
use Src\Application\GlobalCommerce\Inventory\UseCases\DeleteCategoryUseCase;
use Src\Application\GlobalCommerce\Inventory\Services\GcStockTransferService;

class GlobalCommerceServiceProvider extends ServiceProvider
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
            CreateCategoryUseCase::class,
            function ($app) {
                return new CreateCategoryUseCase(
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
            UpdateCategoryUseCase::class,
            function ($app) {
                return new UpdateCategoryUseCase(
                    $app->make(CategoryRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(DeleteProductUseCase::class, function ($app) {
            return new DeleteProductUseCase($app->make(ProductRepositoryInterface::class));
        });

        $this->app->bind(
            DeleteCategoryUseCase::class,
            function ($app) {
                return new DeleteCategoryUseCase(
                    $app->make(CategoryRepositoryInterface::class),
                    $app->make(ProductRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(SaleRepositoryInterface::class, EloquentSaleRepository::class);
        $this->app->bind(
            CreateSaleUseCase::class,
            function ($app) {
                return new CreateSaleUseCase(
                    $app->make(SaleRepositoryInterface::class),
                    $app->make(ProductRepositoryInterface::class),
                    $app->make(\Src\Application\Referral\Services\ReferralService::class)
                );
            }
        );

        $this->app->bind(PurchaseRepositoryInterface::class, EloquentPurchaseRepository::class);
        $this->app->bind(
            CreatePurchaseUseCase::class,
            function ($app) {
                return new CreatePurchaseUseCase(
                    $app->make(PurchaseRepositoryInterface::class),
                    $app->make(ProductRepositoryInterface::class)
                );
            }
        );
        $this->app->bind(
            ReceivePurchaseUseCase::class,
            function ($app) {
                return new ReceivePurchaseUseCase(
                    $app->make(PurchaseRepositoryInterface::class),
                    $app->make(ProductRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(StockTransferRepositoryInterface::class, EloquentStockTransferRepository::class);
        $this->app->bind(StockTransferItemRepositoryInterface::class, EloquentStockTransferItemRepository::class);
        $this->app->bind(GcStockTransferService::class, function ($app) {
            return new GcStockTransferService(
                $app->make(StockTransferRepositoryInterface::class),
                $app->make(StockTransferItemRepositoryInterface::class),
                $app->make(ProductRepositoryInterface::class)
            );
        });
    }

    public function boot(): void
    {
        $migrationsPath = __DIR__ . '/../Migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
