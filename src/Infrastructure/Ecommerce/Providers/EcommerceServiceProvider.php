<?php

namespace Src\Infrastructure\Ecommerce\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Ecommerce\Repositories\OrderRepositoryInterface;
use Src\Domain\Ecommerce\Repositories\OrderItemRepositoryInterface;
use Src\Domain\Ecommerce\Repositories\CustomerRepositoryInterface;
use Src\Infrastructure\Ecommerce\Persistence\EloquentOrderRepository;
use Src\Infrastructure\Ecommerce\Persistence\EloquentOrderItemRepository;
use Src\Infrastructure\Ecommerce\Persistence\EloquentCustomerRepository;
use Src\Application\Ecommerce\UseCases\CreateOrderUseCase;
use Src\Application\Ecommerce\UseCases\UpdateOrderStatusUseCase;
use Src\Application\Ecommerce\UseCases\UpdatePaymentStatusUseCase;
use Src\Application\Ecommerce\UseCases\CreateCustomerUseCase;

class EcommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(
            OrderRepositoryInterface::class,
            EloquentOrderRepository::class
        );

        $this->app->bind(
            OrderItemRepositoryInterface::class,
            EloquentOrderItemRepository::class
        );

        $this->app->bind(
            CustomerRepositoryInterface::class,
            EloquentCustomerRepository::class
        );

        // Use Cases
        $this->app->bind(CreateOrderUseCase::class, function ($app) {
            return new CreateOrderUseCase(
                $app->make(OrderRepositoryInterface::class),
                $app->make(OrderItemRepositoryInterface::class),
                $app->make(\Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface::class)
            );
        });

        $this->app->bind(UpdateOrderStatusUseCase::class, function ($app) {
            return new UpdateOrderStatusUseCase(
                $app->make(OrderRepositoryInterface::class)
            );
        });

        $this->app->bind(UpdatePaymentStatusUseCase::class, function ($app) {
            return new UpdatePaymentStatusUseCase(
                $app->make(OrderRepositoryInterface::class),
                $app->make(\Src\Application\Ecommerce\Services\GenerateDownloadTokensService::class)
            );
        });

        $this->app->bind(CreateCustomerUseCase::class, function ($app) {
            return new CreateCustomerUseCase(
                $app->make(CustomerRepositoryInterface::class)
            );
        });
    }

    public function boot(): void
    {
        // Charger les migrations depuis le dossier du module
        $this->loadMigrationsFrom([
            __DIR__ . '/../Migrations',
        ]);
    }
}
