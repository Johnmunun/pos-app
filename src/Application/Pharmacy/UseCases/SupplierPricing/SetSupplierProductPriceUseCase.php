<?php

declare(strict_types=1);

namespace Src\Application\Pharmacy\UseCases\SupplierPricing;

use DateTimeImmutable;
use Src\Application\Pharmacy\DTO\SetSupplierProductPriceDTO;
use Src\Domain\Pharmacy\Entities\SupplierProductPrice;
use Src\Domain\Pharmacy\Repositories\SupplierProductPriceRepositoryInterface;
use Src\Domain\Pharmacy\ValueObjects\TaxRate;

/**
 * UseCase: SetSupplierProductPriceUseCase
 *
 * Définit ou met à jour le prix d'un produit chez un fournisseur.
 */
final class SetSupplierProductPriceUseCase
{
    public function __construct(
        private readonly SupplierProductPriceRepositoryInterface $priceRepository
    ) {
    }

    public function execute(SetSupplierProductPriceDTO $dto): SupplierProductPrice
    {
        // Chercher si un prix existe déjà
        $existingPrice = $this->priceRepository->findBySupplierAndProduct(
            $dto->supplierId,
            $dto->productId
        );

        $taxRate = new TaxRate($dto->taxRate);
        $effectiveFrom = $dto->effectiveFrom 
            ? new DateTimeImmutable($dto->effectiveFrom) 
            : new DateTimeImmutable();

        if ($existingPrice !== null) {
            // Mettre à jour le prix existant
            $existingPrice->updatePrices(
                $dto->normalPrice,
                $dto->agreedPrice,
                $taxRate,
                $effectiveFrom
            );

            $this->priceRepository->update($existingPrice);

            return $existingPrice;
        }

        // Créer un nouveau prix
        $price = SupplierProductPrice::create(
            $dto->supplierId,
            $dto->productId,
            $dto->normalPrice,
            $dto->agreedPrice,
            $taxRate,
            $effectiveFrom
        );

        $this->priceRepository->save($price);

        return $price;
    }
}
