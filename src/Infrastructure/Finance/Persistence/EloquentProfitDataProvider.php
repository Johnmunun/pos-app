<?php

namespace Src\Infrastructure\Finance\Persistence;

use Src\Domain\Finance\Repositories\ProfitDataProviderInterface;
use Src\Infrastructure\Pharmacy\Models\SaleLineModel;
use Src\Infrastructure\Pharmacy\Models\SaleModel;

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
            $result[] = [
                'product_id' => $line->product_id,
                'quantity' => (int) $line->quantity,
                'unit_price' => (float) $line->unit_price_amount,
                'currency' => $line->currency ?? 'CDF',
                'product_name' => $product->name ?? '—',
                'unit_cost' => $product && $product->cost_amount !== null ? (float) $product->cost_amount : 0.0,
            ];
        }
        return $result;
    }
}
