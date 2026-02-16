<?php

namespace Src\Application\Pharmacy\UseCases\Sales;

use Src\Domain\Pharmacy\Entities\Sale;
use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;

class CreateDraftSaleUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository
    ) {}

    public function execute(string $shopId, ?string $customerId, string $currency, int $createdBy): Sale
    {
        $sale = Sale::createDraft(
            $shopId,
            $customerId,
            $currency,
            $createdBy
        );

        $this->saleRepository->save($sale);

        return $sale;
    }
}

