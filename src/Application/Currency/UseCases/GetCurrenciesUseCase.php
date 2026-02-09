<?php

namespace Src\Application\Currency\UseCases;

use Src\Domain\Currency\Repositories\CurrencyRepositoryInterface;

/**
 * Use Case: GetCurrenciesUseCase
 * 
 * Récupère toutes les devises d'un tenant
 */
class GetCurrenciesUseCase
{
    public function __construct(
        private CurrencyRepositoryInterface $repository
    ) {}

    /**
     * @return \Src\Domain\Currency\Entities\Currency[]
     */
    public function execute(int $tenantId): array
    {
        return $this->repository->findByTenantId($tenantId);
    }
}
