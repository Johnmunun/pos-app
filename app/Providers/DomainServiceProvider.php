<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\EloquentTenantRepository;
use Domains\Tenant\Repositories\TenantRepository;
use Domains\Tenant\Services\TenantService;

/**
 * Service Provider: DomainServiceProvider
 *
 * Enregistre tous les bindings du container Laravel.
 *
 * Responsabilités:
 * - Associer les interfaces domain aux implémentations infrastructure
 * - Enregistrer les services
 * - Configurer l'injection de dépendances
 *
 * Cela permet à Laravel d'injecter automatiquement les bonnes dépendances
 * sans créer de couplage direct au framework.
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * Enregistrer les services dans le container
     */
    public function register(): void
    {
        // Associer l'interface du repository à son implémentation Eloquent
        $this->app->bind(
            TenantRepository::class,
            EloquentTenantRepository::class
        );

        // Enregistrer les services domaines
        $this->app->singleton(TenantService::class, function ($app) {
            return new TenantService(
                repository: $app->make(TenantRepository::class)
            );
        });

        // Les Use Cases seront enregistrés ici aussi au fur et à mesure
    }

    /**
     * Bootstrap des services
     */
    public function boot(): void
    {
        //
    }
}
