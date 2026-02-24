<?php

namespace Src\Application\Pharmacy\UseCases\Sales;

use Src\Domain\Pharmacy\Entities\Sale;
use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;

class CreateDraftSaleUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository
    ) {}

    public function execute(string $shopId, ?string $customerId, string $currency, int $createdBy, ?int $cashRegisterId = null, ?int $cashRegisterSessionId = null, string $saleType = Sale::SALE_TYPE_RETAIL): Sale
    {
        $sale = Sale::createDraft(
            $shopId,
            $customerId,
            $currency,
            $createdBy,
            $cashRegisterId,
            $cashRegisterSessionId,
            $saleType
        );

        $this->saleRepository->save($sale);

        return $sale;
    }
}

