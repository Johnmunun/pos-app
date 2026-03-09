<?php

namespace Src\Infrastructure\Ecommerce\Services;

use Src\Infrastructure\Ecommerce\Models\ShippingMethodModel;

class ShippingCostService
{
    /**
     * Calcule les frais de livraison selon la méthode et le contenu du panier.
     *
     * @param string $methodId
     * @param float $cartSubtotal Montant HT du panier
     * @param float $cartWeight Poids total en kg (optionnel, pour weight_based)
     * @param string $shopId
     * @return array{amount: float, method_name: string}|null
     */
    public function calculate(string $methodId, float $cartSubtotal, float $cartWeight, string $shopId): ?array
    {
        $method = ShippingMethodModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->find($methodId);

        if (!$method) {
            return null;
        }

        $baseCost = (float) $method->base_cost;
        $freeThreshold = $method->free_shipping_threshold ? (float) $method->free_shipping_threshold : null;

        switch ($method->type) {
            case 'free':
                return [
                    'amount' => 0,
                    'method_name' => $method->name,
                    'method_id' => $method->id,
                ];
            case 'flat_rate':
                $amount = $baseCost;
                if ($freeThreshold !== null && $cartSubtotal >= $freeThreshold) {
                    $amount = 0;
                }
                return [
                    'amount' => $amount,
                    'method_name' => $method->name,
                    'method_id' => $method->id,
                ];
            case 'price_based':
                $amount = $this->calculateFromPriceRanges(
                    $cartSubtotal,
                    $method->price_ranges ?? [],
                    $baseCost,
                    $freeThreshold
                );
                return [
                    'amount' => $amount,
                    'method_name' => $method->name,
                    'method_id' => $method->id,
                ];
            case 'weight_based':
                $amount = $this->calculateFromWeightRanges(
                    $cartWeight,
                    $method->weight_ranges ?? [],
                    $baseCost,
                    $freeThreshold,
                    $cartSubtotal
                );
                return [
                    'amount' => $amount,
                    'method_name' => $method->name,
                    'method_id' => $method->id,
                ];
            default:
                return [
                    'amount' => $baseCost,
                    'method_name' => $method->name,
                    'method_id' => $method->id,
                ];
        }
    }

    private function calculateFromPriceRanges(float $subtotal, array $ranges, float $defaultCost, ?float $freeThreshold): float
    {
        if ($freeThreshold !== null && $subtotal >= $freeThreshold) {
            return 0;
        }
        foreach ($ranges as $range) {
            $min = (float) ($range['min'] ?? 0);
            $max = (float) ($range['max'] ?? PHP_FLOAT_MAX);
            if ($subtotal >= $min && $subtotal < $max) {
                return (float) ($range['cost'] ?? $defaultCost);
            }
        }
        return $defaultCost;
    }

    private function calculateFromWeightRanges(float $weight, array $ranges, float $defaultCost, ?float $freeThreshold, float $subtotal): float
    {
        if ($freeThreshold !== null && $subtotal >= $freeThreshold) {
            return 0;
        }
        foreach ($ranges as $range) {
            $min = (float) ($range['min'] ?? 0);
            $max = (float) ($range['max'] ?? PHP_FLOAT_MAX);
            if ($weight >= $min && $weight < $max) {
                return (float) ($range['cost'] ?? $defaultCost);
            }
        }
        return $defaultCost;
    }
}
