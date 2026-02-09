<?php

namespace Src\Application\Currency\UseCases;

use Src\Application\Currency\DTO\UpdateCurrencyDTO;
use Src\Domain\Currency\Repositories\CurrencyRepositoryInterface;
use Src\Domain\Currency\ValueObjects\CurrencyCode;
use Src\Domain\Currency\ValueObjects\CurrencyName;
use Src\Domain\Currency\ValueObjects\CurrencySymbol;

/**
 * Use Case: UpdateCurrencyUseCase
 * 
 * Met à jour une devise existante
 */
class UpdateCurrencyUseCase
{
    public function __construct(
        private CurrencyRepositoryInterface $repository
    ) {}

    public function execute(UpdateCurrencyDTO $dto): void
    {
        $currency = $this->repository->findById($dto->id);
        if ($currency === null) {
            throw new \DomainException("Currency with ID {$dto->id} not found");
        }

        // Vérifier l'unicité du code si modifié
        if ($dto->code !== null) {
            $existing = $this->repository->findByCodeAndTenant($dto->code, $currency->getTenantId());
            if ($existing !== null && $existing->getId() !== $currency->getId()) {
                throw new \DomainException("Currency with code '{$dto->code}' already exists for this tenant");
            }
        }

        // Si on définit comme par défaut, désactiver les autres
        if ($dto->isDefault === true) {
            $this->repository->unsetAllDefaultsForTenant($currency->getTenantId());
            $currency->setAsDefault();
        } elseif ($dto->isDefault === false) {
            $currency->unsetAsDefault();
        }

        // Mettre à jour les valeurs
        $code = $dto->code !== null ? new CurrencyCode($dto->code) : null;
        $name = $dto->name !== null ? new CurrencyName($dto->name) : null;
        $symbol = $dto->symbol !== null ? new CurrencySymbol($dto->symbol) : null;

        $currency->update($code, $name, $symbol);

        $this->repository->save($currency);
    }
}
