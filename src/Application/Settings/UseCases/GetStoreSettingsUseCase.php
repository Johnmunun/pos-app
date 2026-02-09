<?php

namespace Src\Application\Settings\UseCases;

use Src\Domain\Settings\Repositories\StoreSettingsRepositoryInterface;
use Src\Domain\Settings\Entities\StoreSettings;

/**
 * Use Case: GetStoreSettings
 * 
 * Récupère les paramètres d'une boutique
 * Sécurité: Le shopId doit venir de l'utilisateur connecté (vérifié dans le Controller)
 */
class GetStoreSettingsUseCase
{
    public function __construct(
        private StoreSettingsRepositoryInterface $repository
    ) {}

    public function execute(string $shopId): ?StoreSettings
    {
        return $this->repository->findByShopId($shopId);
    }
}
