<?php

namespace Src\Application\Currency\UseCases;

use Src\Domain\Currency\Repositories\CurrencyRepositoryInterface;

/**
 * Use Case: DeleteCurrencyUseCase
 * 
 * Supprime une devise
 */
class DeleteCurrencyUseCase
{
    public function __construct(
        private CurrencyRepositoryInterface $repository
    ) {}

    public function execute(int $currencyId): void
    {
        $currency = $this->repository->findById($currencyId);
        if ($currency === null) {
            throw new \DomainException("Currency with ID {$currencyId} not found");
        }

        // Ne pas supprimer la devise par défaut
        if ($currency->isDefault()) {
            throw new \DomainException("Cannot delete default currency");
        }

        $this->repository->delete($currency);
    }
}
