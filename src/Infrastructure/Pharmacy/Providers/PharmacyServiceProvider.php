<?php

namespace Src\Infrastructure\Pharmacy\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\CategoryRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\BatchRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\StockMovementRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SaleLineRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\PurchaseOrderRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\PurchaseOrderLineRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SupplierRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SupplierProductPriceRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\CustomerRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\ProductBatchRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\InventoryRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\InventoryItemRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\StockTransferRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\StockTransferItemRepositoryInterface;
use Src\Domain\Pharmacy\Services\InventoryService as DomainInventoryService;
use Src\Domain\Pharmacy\Services\ExpiryAlertService;
use Src\Application\Pharmacy\Services\InventoryService;
use Src\Infrastructure\Pharmacy\Persistence\EloquentProductRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentCategoryRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentBatchRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentStockMovementRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentSaleRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentSaleLineRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentPurchaseOrderRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentPurchaseOrderLineRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentSupplierRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentSupplierProductPriceRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentCustomerRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentProductBatchRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentInventoryRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentInventoryItemRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentStockTransferRepository;
use Src\Infrastructure\Pharmacy\Persistence\EloquentStockTransferItemRepository;
use Src\Application\Pharmacy\Services\StockTransferService;
use Src\Application\Pharmacy\UseCases\Product\CreateProductUseCase;
use Src\Application\Pharmacy\UseCases\Product\UpdateProductUseCase;
use Src\Application\Pharmacy\UseCases\Inventory\UpdateStockUseCase;
use Src\Application\Pharmacy\UseCases\Category\CreateCategoryUseCase;
use Src\Application\Pharmacy\UseCases\Category\UpdateCategoryUseCase;
use Src\Application\Pharmacy\UseCases\Category\DeleteCategoryUseCase;
use Src\Application\Pharmacy\UseCases\Sales\CreateDraftSaleUseCase;
use Src\Application\Pharmacy\UseCases\Sales\UpdateSaleLinesUseCase;
use Src\Application\Pharmacy\UseCases\Sales\FinalizeSaleUseCase;
use Src\Application\Pharmacy\UseCases\Sales\CancelSaleUseCase;
use Src\Application\Pharmacy\UseCases\Sales\AttachCustomerToSaleUseCase;
use Src\Application\Pharmacy\UseCases\Purchases\CreatePurchaseOrderUseCase;
use Src\Application\Pharmacy\UseCases\Purchases\ConfirmPurchaseOrderUseCase;
use Src\Application\Pharmacy\UseCases\Purchases\ReceivePurchaseOrderUseCase;
use Src\Application\Pharmacy\UseCases\Purchases\CancelPurchaseOrderUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\CreateSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\UpdateSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\ActivateSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\DeactivateSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\GetSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\ListSuppliersUseCase;
use Src\Application\Pharmacy\UseCases\SupplierPricing\SetSupplierProductPriceUseCase;
use Src\Application\Pharmacy\UseCases\SupplierPricing\GetSupplierProductPriceUseCase;
use Src\Application\Pharmacy\UseCases\SupplierPricing\ListSupplierPricesUseCase;
use Src\Application\Pharmacy\UseCases\Customer\CreateCustomerUseCase;
use Src\Application\Pharmacy\UseCases\Customer\UpdateCustomerUseCase;
use Src\Application\Pharmacy\UseCases\Customer\ActivateCustomerUseCase;
use Src\Application\Pharmacy\UseCases\Customer\DeactivateCustomerUseCase;
use Src\Application\Pharmacy\UseCases\Customer\GetCustomerUseCase;
use Src\Application\Pharmacy\UseCases\Customer\ListCustomersUseCase;
use Src\Application\Pharmacy\UseCases\Batch\AddBatchUseCase;
use Src\Application\Pharmacy\UseCases\Batch\DecreaseBatchUseCase;
use Src\Application\Pharmacy\UseCases\Batch\GetExpiringBatchesUseCase;
use Src\Application\Pharmacy\UseCases\Batch\GetExpiredBatchesUseCase;
use Src\Application\Pharmacy\UseCases\Batch\ListBatchesUseCase;
use Src\Application\Pharmacy\UseCases\Batch\GetBatchSummaryUseCase;
use Src\Application\Pharmacy\Services\DashboardService;
use Src\Infrastructure\Pharmacy\Services\PharmacyExportService;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Infrastructure\Settings\Services\StoreLogoService;

class PharmacyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Domain Services
        $this->app->bind(DomainInventoryService::class, function () {
            return new DomainInventoryService();
        });
        
        $this->app->bind(ExpiryAlertService::class, function () {
            return new ExpiryAlertService();
        });

        // Inventory Repositories
        $this->app->bind(
            InventoryRepositoryInterface::class,
            EloquentInventoryRepository::class
        );

        $this->app->bind(
            InventoryItemRepositoryInterface::class,
            EloquentInventoryItemRepository::class
        );

        // Inventory Service (Application Layer)
        $this->app->bind(InventoryService::class, function ($app) {
            return new InventoryService(
                $app->make(InventoryRepositoryInterface::class),
                $app->make(InventoryItemRepositoryInterface::class),
                $app->make(ProductRepositoryInterface::class),
                $app->make(StockMovementRepositoryInterface::class)
            );
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

        $this->app->bind(
            StockMovementRepositoryInterface::class,
            EloquentStockMovementRepository::class
        );

        $this->app->bind(
            SaleRepositoryInterface::class,
            EloquentSaleRepository::class
        );

        $this->app->bind(
            SaleLineRepositoryInterface::class,
            EloquentSaleLineRepository::class
        );

        $this->app->bind(
            PurchaseOrderRepositoryInterface::class,
            EloquentPurchaseOrderRepository::class
        );

        $this->app->bind(
            PurchaseOrderLineRepositoryInterface::class,
            EloquentPurchaseOrderLineRepository::class
        );

        $this->app->bind(
            SupplierRepositoryInterface::class,
            EloquentSupplierRepository::class
        );

        $this->app->bind(
            SupplierProductPriceRepositoryInterface::class,
            EloquentSupplierProductPriceRepository::class
        );

        $this->app->bind(
            CustomerRepositoryInterface::class,
            EloquentCustomerRepository::class
        );

        $this->app->bind(
            ProductBatchRepositoryInterface::class,
            EloquentProductBatchRepository::class
        );

        // Stock Transfer Repositories
        $this->app->bind(
            StockTransferRepositoryInterface::class,
            EloquentStockTransferRepository::class
        );

        $this->app->bind(
            StockTransferItemRepositoryInterface::class,
            EloquentStockTransferItemRepository::class
        );

        // Stock Transfer Service
        $this->app->bind(
            StockTransferService::class,
            function ($app) {
                return new StockTransferService(
                    $app->make(StockTransferRepositoryInterface::class),
                    $app->make(StockTransferItemRepositoryInterface::class),
                    $app->make(ProductRepositoryInterface::class),
                    $app->make(StockMovementRepositoryInterface::class)
                );
            }
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

        // Export Service
        $this->app->bind(
            PharmacyExportService::class,
            function ($app) {
                return new PharmacyExportService(
                    $app->make(GetStoreSettingsUseCase::class),
                    $app->make(StoreLogoService::class)
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
                    $app->make(BatchRepositoryInterface::class),
                    $app->make(StockMovementRepositoryInterface::class)
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

        // Sales Use Cases
        $this->app->bind(CreateDraftSaleUseCase::class, function ($app) {
            return new CreateDraftSaleUseCase($app->make(SaleRepositoryInterface::class));
        });
        $this->app->bind(UpdateSaleLinesUseCase::class, function ($app) {
            return new UpdateSaleLinesUseCase(
                $app->make(SaleRepositoryInterface::class),
                $app->make(SaleLineRepositoryInterface::class),
                $app->make(ProductRepositoryInterface::class)
            );
        });
        $this->app->bind(FinalizeSaleUseCase::class, function ($app) {
            return new FinalizeSaleUseCase(
                $app->make(SaleRepositoryInterface::class),
                $app->make(SaleLineRepositoryInterface::class),
                $app->make(UpdateStockUseCase::class)
            );
        });
        $this->app->bind(CancelSaleUseCase::class, function ($app) {
            return new CancelSaleUseCase($app->make(SaleRepositoryInterface::class));
        });
        $this->app->bind(AttachCustomerToSaleUseCase::class, function ($app) {
            return new AttachCustomerToSaleUseCase($app->make(SaleRepositoryInterface::class));
        });

        // Purchases Use Cases
        $this->app->bind(CreatePurchaseOrderUseCase::class, function ($app) {
            return new CreatePurchaseOrderUseCase(
                $app->make(PurchaseOrderRepositoryInterface::class),
                $app->make(PurchaseOrderLineRepositoryInterface::class),
                $app->make(ProductRepositoryInterface::class)
            );
        });
        $this->app->bind(ConfirmPurchaseOrderUseCase::class, function ($app) {
            return new ConfirmPurchaseOrderUseCase($app->make(PurchaseOrderRepositoryInterface::class));
        });
        $this->app->bind(ReceivePurchaseOrderUseCase::class, function ($app) {
            return new ReceivePurchaseOrderUseCase(
                $app->make(PurchaseOrderRepositoryInterface::class),
                $app->make(PurchaseOrderLineRepositoryInterface::class),
                $app->make(UpdateStockUseCase::class),
                $app->make(AddBatchUseCase::class)
            );
        });
        $this->app->bind(CancelPurchaseOrderUseCase::class, function ($app) {
            return new CancelPurchaseOrderUseCase($app->make(PurchaseOrderRepositoryInterface::class));
        });

        // Supplier Use Cases
        $this->app->bind(CreateSupplierUseCase::class, function ($app) {
            return new CreateSupplierUseCase($app->make(SupplierRepositoryInterface::class));
        });
        $this->app->bind(UpdateSupplierUseCase::class, function ($app) {
            return new UpdateSupplierUseCase($app->make(SupplierRepositoryInterface::class));
        });
        $this->app->bind(ActivateSupplierUseCase::class, function ($app) {
            return new ActivateSupplierUseCase($app->make(SupplierRepositoryInterface::class));
        });
        $this->app->bind(DeactivateSupplierUseCase::class, function ($app) {
            return new DeactivateSupplierUseCase($app->make(SupplierRepositoryInterface::class));
        });
        $this->app->bind(GetSupplierUseCase::class, function ($app) {
            return new GetSupplierUseCase($app->make(SupplierRepositoryInterface::class));
        });
        $this->app->bind(ListSuppliersUseCase::class, function ($app) {
            return new ListSuppliersUseCase($app->make(SupplierRepositoryInterface::class));
        });

        // Supplier Pricing Use Cases
        $this->app->bind(SetSupplierProductPriceUseCase::class, function ($app) {
            return new SetSupplierProductPriceUseCase($app->make(SupplierProductPriceRepositoryInterface::class));
        });
        $this->app->bind(GetSupplierProductPriceUseCase::class, function ($app) {
            return new GetSupplierProductPriceUseCase($app->make(SupplierProductPriceRepositoryInterface::class));
        });
        $this->app->bind(ListSupplierPricesUseCase::class, function ($app) {
            return new ListSupplierPricesUseCase($app->make(SupplierProductPriceRepositoryInterface::class));
        });

        // Customer Use Cases
        $this->app->bind(CreateCustomerUseCase::class, function ($app) {
            return new CreateCustomerUseCase($app->make(CustomerRepositoryInterface::class));
        });
        $this->app->bind(UpdateCustomerUseCase::class, function ($app) {
            return new UpdateCustomerUseCase($app->make(CustomerRepositoryInterface::class));
        });
        $this->app->bind(ActivateCustomerUseCase::class, function ($app) {
            return new ActivateCustomerUseCase($app->make(CustomerRepositoryInterface::class));
        });
        $this->app->bind(DeactivateCustomerUseCase::class, function ($app) {
            return new DeactivateCustomerUseCase($app->make(CustomerRepositoryInterface::class));
        });
        $this->app->bind(GetCustomerUseCase::class, function ($app) {
            return new GetCustomerUseCase($app->make(CustomerRepositoryInterface::class));
        });
        $this->app->bind(ListCustomersUseCase::class, function ($app) {
            return new ListCustomersUseCase($app->make(CustomerRepositoryInterface::class));
        });

        // Batch Use Cases
        $this->app->bind(AddBatchUseCase::class, function ($app) {
            return new AddBatchUseCase(
                $app->make(ProductBatchRepositoryInterface::class),
                $app->make(ProductRepositoryInterface::class)
            );
        });
        $this->app->bind(DecreaseBatchUseCase::class, function ($app) {
            return new DecreaseBatchUseCase($app->make(ProductBatchRepositoryInterface::class));
        });
        $this->app->bind(GetExpiringBatchesUseCase::class, function ($app) {
            return new GetExpiringBatchesUseCase($app->make(ProductBatchRepositoryInterface::class));
        });
        $this->app->bind(GetExpiredBatchesUseCase::class, function ($app) {
            return new GetExpiredBatchesUseCase($app->make(ProductBatchRepositoryInterface::class));
        });
        $this->app->bind(ListBatchesUseCase::class, function ($app) {
            return new ListBatchesUseCase($app->make(ProductBatchRepositoryInterface::class));
        });
        $this->app->bind(GetBatchSummaryUseCase::class, function ($app) {
            return new GetBatchSummaryUseCase($app->make(ProductBatchRepositoryInterface::class));
        });
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}