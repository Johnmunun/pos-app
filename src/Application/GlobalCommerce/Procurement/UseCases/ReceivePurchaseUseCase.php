<?php

namespace Src\Application\GlobalCommerce\Procurement\UseCases;

use Src\Domain\GlobalCommerce\Procurement\Repositories\PurchaseRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Shared\ValueObjects\Quantity;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseLineModel;
use Illuminate\Support\Facades\DB;

final class ReceivePurchaseUseCase
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchaseRepository,
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    public function execute(string $shopId, string $purchaseId): void
    {
        $purchase = $this->purchaseRepository->findById($purchaseId);
        if (!$purchase || $purchase->getShopId() !== $shopId) {
            throw new \InvalidArgumentException('Bon de commande introuvable.');
        }
        if ($purchase->isReceived()) {
            throw new \InvalidArgumentException('Ce bon a déjà été réceptionné.');
        }

        foreach ($purchase->getLines() as $line) {
            $product = $this->productRepository->findById($line['product_id']);
            if (!$product || $product->getShopId() !== $shopId) {
                continue;
            }
            $qty = $line['ordered_quantity'];
            $product->addStock(new Quantity($qty));
            $this->productRepository->update($product);
        }

        $model = PurchaseModel::find($purchaseId);
        if ($model) {
            $model->update(['status' => 'received', 'received_at' => now()]);
            PurchaseLineModel::where('purchase_id', $purchaseId)->update(['received_quantity' => DB::raw('ordered_quantity')]);
        }
    }
}
