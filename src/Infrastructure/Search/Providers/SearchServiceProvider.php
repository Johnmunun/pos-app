<?php

namespace Src\Infrastructure\Search\Providers;

use Src\Application\GlobalSearch\Repositories\GlobalSearchRepositoryInterface;
use Src\Application\GlobalSearch\UseCases\SearchGlobalUseCase;
use Illuminate\Support\ServiceProvider;
use Src\Infrastructure\Search\Repositories\GlobalSearchProvider;

/**
 * Service Provider: SearchServiceProvider
 *
 * Enregistre les bindings pour le module de recherche globale.
 *
 * @package Infrastructure\Search\Providers
 */
class SearchServiceProvider extends ServiceProvider
{
    /**
     * Enregistrer les services dans le container
     */
    public function register(): void
    {
        // Associer l'interface du repository à son implémentation
        $this->app->bind(
            GlobalSearchRepositoryInterface::class,
            GlobalSearchProvider::class
        );

        // Enregistrer le Use Case
        $this->app->bind(SearchGlobalUseCase::class, function ($app) {
            return new SearchGlobalUseCase(
                searchRepository: $app->make(GlobalSearchRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap des services
     */
    public function boot(): void
    {
        //
    }
}
