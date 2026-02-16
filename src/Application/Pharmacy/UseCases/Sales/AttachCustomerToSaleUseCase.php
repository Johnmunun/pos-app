<?php

namespace Src\Application\Pharmacy\UseCases\Sales;

use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;

class AttachCustomerToSaleUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository
    ) {}

    public function execute(string $saleId, string $customerId): void
    {
        $sale = $this->saleRepository->findById($saleId);

        if (!$sale) {
            throw new \InvalidArgumentException('Sale not found');
        }

        if ($sale->getStatus() !== \Src\Domain\Pharmacy\Entities\Sale::STATUS_DRAFT) {
            throw new \LogicException('Only draft sales can be updated');
        }

        $sale->attachCustomer($customerId);
        $this->saleRepository->save($sale);
    }
}

