<?php

namespace Src\Infrastructure\Finance\Persistence;

use Src\Domain\Finance\Repositories\ProfitDataProviderInterface;
use Src\Infrastructure\Pharmacy\Models\SaleLineModel;
use Src\Infrastructure\Pharmacy\Models\SaleModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel as HardwareProductModel;

/**
 * Requête unique indexée : ventes COMPLETED par shop et période, avec lignes et coût produit.
 */
class EloquentProfitDataProvider implements ProfitDataProviderInterface
{
    public function getCompletedSaleLinesForPeriod(string $shopId, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array
    {
        $query = SaleModel::where('shop_id', $shopId)->where('status', 'COMPLETED');
        if ($from) {
            $query->where('completed_at', '>=', $from->setTime(0, 0, 0)->format('Y-m-d H:i:s'));
        }
        if ($to) {
            $query->where('completed_at', '<=', $to->setTime(23, 59, 59)->format('Y-m-d H:i:s'));
        }
        $saleIds = $query->pluck('id')->all();
        if (empty($saleIds)) {
            return [];
        }

        $lines = SaleLineModel::with('product')
            ->whereIn('sale_id', $saleIds)
            ->get();

        $result = [];
        foreach ($lines as $line) {
            $product = $line->relationLoaded('product') ? $line->product : null;

            // Résolution du nom de produit :
            // 1) Produit pharmacie (relation existante)
            // 2) Sinon, tentative via produit quincaillerie (Hardware)
            $productName = '—';
            if ($product && !empty($product->name)) {
                $productName = $product->name;
            } else {
                $hardware = HardwareProductModel::find($line->product_id);
                if ($hardware && !empty($hardware->name)) {
                    $productName = $hardware->name;
                }
            }
            $result[] = [
                'product_id' => $line->product_id,
                'quantity' => (int) $line->quantity,
                'unit_price' => (float) $line->unit_price_amount,
                'currency' => $line->currency ?? 'CDF',
                'product_name' => $productName,
                'unit_cost' => $product && $product->cost_amount !== null ? (float) $product->cost_amount : 0.0,
            ];
        }
        return $result;
    }
}
