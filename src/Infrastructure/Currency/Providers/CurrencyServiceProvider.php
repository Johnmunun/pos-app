<?php

namespace Src\Infrastructure\Currency\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Currency\Repositories\CurrencyRepositoryInterface;
use Src\Infrastructure\Currency\Persistence\EloquentCurrencyRepository;
use Src\Application\Currency\UseCases\GetCurrenciesUseCase;
use Src\Application\Currency\UseCases\CreateCurrencyUseCase;
use Src\Application\Currency\UseCases\UpdateCurrencyUseCase;
use Src\Application\Currency\UseCases\DeleteCurrencyUseCase;

/**
 * Service Provider: CurrencyServiceProvider
 * 
 * Lie les interfaces du domaine aux implémentations de l'infrastructure
 */
class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository
        $this->app->bind(
            CurrencyRepositoryInterface::class,
            EloquentCurrencyRepository::class
        );

        // Use Cases
        $this->app->bind(
            GetCurrenciesUseCase::class,
            function ($app) {
                return new GetCurrenciesUseCase(
                    $app->make(CurrencyRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            CreateCurrencyUseCase::class,
            function ($app) {
                return new CreateCurrencyUseCase(
                    $app->make(CurrencyRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            UpdateCurrencyUseCase::class,
            function ($app) {
                return new UpdateCurrencyUseCase(
                    $app->make(CurrencyRepositoryInterface::class)
                );
            }
        );

        $this->app->bind(
            DeleteCurrencyUseCase::class,
            function ($app) {
                return new DeleteCurrencyUseCase(
                    $app->make(CurrencyRepositoryInterface::class)
                );
            }
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
