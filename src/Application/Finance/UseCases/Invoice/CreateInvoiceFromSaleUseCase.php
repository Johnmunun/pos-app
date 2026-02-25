<?php

namespace Src\Application\Finance\UseCases\Invoice;

use Src\Domain\Finance\Entities\Invoice;
use Src\Domain\Finance\Repositories\InvoiceRepositoryInterface;
use Src\Domain\Finance\Services\InvoiceNumberGeneratorService;
use Src\Shared\ValueObjects\Money;

final class CreateInvoiceFromSaleUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceNumberGeneratorService $numberGenerator
    ) {}

    public function execute(
        string $tenantId,
        string $shopId,
        string $saleId,
        Money $totalAmount,
        Money $paidAmount,
        string $status = Invoice::STATUS_DRAFT
    ): Invoice {
        $number = $this->numberGenerator->generateNext($shopId, 'INV');
        $issuedAt = new \DateTimeImmutable();
        $invoice = new Invoice(
            \Ramsey\Uuid\Uuid::uuid4()->toString(),
            $tenantId,
            $shopId,
            $number,
            Invoice::SOURCE_SALE,
            $saleId,
            $totalAmount,
            $paidAmount,
            $status,
            $issuedAt,
            $status !== Invoice::STATUS_DRAFT ? $issuedAt : null,
            $status === Invoice::STATUS_PAID ? $issuedAt : null
        );
        $this->invoiceRepository->save($invoice);
        return $invoice;
    }
}
