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

        // User Repository
        $this->app->bind(
            \Domains\User\Repositories\UserRepository::class,
            \App\Repositories\EloquentUserRepository::class
        );

        // Enregistrer les services domaines
        $this->app->singleton(TenantService::class, function ($app) {
            return new TenantService(
                repository: $app->make(TenantRepository::class)
            );
        });

        // User Management Use Cases
        $this->app->bind(
            \Src\Domains\User\UseCases\AssignUserRoleUseCase::class,
            function ($app) {
                return new \Src\Domains\User\UseCases\AssignUserRoleUseCase(
                    userRepository: $app->make(\Domains\User\Repositories\UserRepository::class)
                );
            }
        );

        $this->app->bind(
            \Src\Domains\User\UseCases\UpdateUserStatusUseCase::class,
            function ($app) {
                return new \Src\Domains\User\UseCases\UpdateUserStatusUseCase(
                    userRepository: $app->make(\Domains\User\Repositories\UserRepository::class)
                );
            }
        );

        $this->app->bind(
            \Src\Domains\User\UseCases\ResetUserPasswordUseCase::class,
            function ($app) {
                return new \Src\Domains\User\UseCases\ResetUserPasswordUseCase(
                    userRepository: $app->make(\Domains\User\Repositories\UserRepository::class)
                );
            }
        );

        $this->app->bind(
            \Src\Domains\User\UseCases\DeleteUserUseCase::class,
            function ($app) {
                return new \Src\Domains\User\UseCases\DeleteUserUseCase(
                    userRepository: $app->make(\Domains\User\Repositories\UserRepository::class)
                );
            }
        );

        $this->app->bind(
            \Src\Domains\User\UseCases\ImpersonateUserUseCase::class,
            function ($app) {
                return new \Src\Domains\User\UseCases\ImpersonateUserUseCase(
                    userRepository: $app->make(\Domains\User\Repositories\UserRepository::class)
                );
            }
        );

        $this->app->bind(
            \Src\Domains\User\UseCases\StopImpersonationUseCase::class,
            function ($app) {
                return new \Src\Domains\User\UseCases\StopImpersonationUseCase();
            }
        );

        // User Service (déjà enregistré via singleton si nécessaire)
        // Le UserService sera résolu automatiquement par Laravel via l'injection de dépendances

        // Module Permission Service
        $this->app->singleton(
            \Src\Domains\User\Services\ModulePermissionService::class,
            function ($app) {
                return new \Src\Domains\User\Services\ModulePermissionService();
            }
        );

        // Pharmacy Use Cases
        $this->app->bind(
            \Src\Application\Pharmacy\UseCases\Seller\CreateSellerUseCase::class,
            function ($app) {
                return new \Src\Application\Pharmacy\UseCases\Seller\CreateSellerUseCase(
                    userService: $app->make(\Domains\User\Services\UserService::class),
                    assignRoleUseCase: $app->make(\Src\Domains\User\UseCases\AssignUserRoleUseCase::class),
                    modulePermissionService: $app->make(\Src\Domains\User\Services\ModulePermissionService::class)
                );
            }
        );
    }

    /**
     * Bootstrap des services
     */
    public function boot(): void
    {
        //
    }
}
