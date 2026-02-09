<?php

namespace Src\Application\Currency\UseCases;

use Src\Application\Currency\DTO\CreateCurrencyDTO;
use Src\Domain\Currency\Entities\Currency;
use Src\Domain\Currency\Repositories\CurrencyRepositoryInterface;
use Src\Domain\Currency\ValueObjects\CurrencyCode;
use Src\Domain\Currency\ValueObjects\CurrencyName;
use Src\Domain\Currency\ValueObjects\CurrencySymbol;

/**
 * Use Case: CreateCurrencyUseCase
 * 
 * Crée une nouvelle devise
 */
class CreateCurrencyUseCase
{
    public function __construct(
        private CurrencyRepositoryInterface $repository
    ) {}

    public function execute(CreateCurrencyDTO $dto): Currency
    {
        // Vérifier l'unicité du code pour ce tenant
        $existing = $this->repository->findByCodeAndTenant($dto->code, $dto->tenantId);
        if ($existing !== null) {
            throw new \DomainException("Currency with code '{$dto->code}' already exists for this tenant");
        }

        // Si c'est la devise par défaut, désactiver les autres
        if ($dto->isDefault) {
            $this->repository->unsetAllDefaultsForTenant($dto->tenantId);
        }

        // Créer la devise
        $currency = new Currency(
            id: 0, // Sera assigné par le repository
            tenantId: $dto->tenantId,
            code: new CurrencyCode($dto->code),
            name: new CurrencyName($dto->name),
            symbol: new CurrencySymbol($dto->symbol),
            isDefault: $dto->isDefault,
            isActive: true
        );

        $this->repository->save($currency);

        return $currency;
    }
}
