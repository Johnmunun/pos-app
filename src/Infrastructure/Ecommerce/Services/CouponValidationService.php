<?php

namespace Src\Infrastructure\Ecommerce\Services;

use Carbon\Carbon;
use Src\Infrastructure\Ecommerce\Models\CouponModel;

class CouponValidationService
{
    /**
     * Valide un code coupon et calcule la réduction applicable.
     *
     * @param string $code
     * @param string $shopId
     * @param float $cartSubtotal
     * @param array{array{product_id: string, quantity: int, unit_price: float}} $cartItems
     * @return array{valid: bool, discount_amount: float, message?: string}
     */
    public function validate(string $code, string $shopId, float $cartSubtotal, array $cartItems): array
    {
        $code = strtoupper(trim($code));
        if (empty($code)) {
            return ['valid' => false, 'discount_amount' => 0, 'message' => 'Code requis.'];
        }

        $coupon = CouponModel::where('shop_id', $shopId)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return ['valid' => false, 'discount_amount' => 0, 'message' => 'Code invalide ou inactif.'];
        }

        $now = Carbon::now();
        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            return ['valid' => false, 'discount_amount' => 0, 'message' => 'Ce code n\'est pas encore valide.'];
        }
        if ($coupon->ends_at && $now->gt($coupon->ends_at)) {
            return ['valid' => false, 'discount_amount' => 0, 'message' => 'Ce code a expiré.'];
        }

        if ($coupon->maximum_uses !== null && $coupon->used_count >= $coupon->maximum_uses) {
            return ['valid' => false, 'discount_amount' => 0, 'message' => 'Ce code a atteint sa limite d\'utilisation.'];
        }

        if ($coupon->minimum_purchase !== null && $cartSubtotal < (float) $coupon->minimum_purchase) {
            return [
                'valid' => false,
                'discount_amount' => 0,
                'message' => 'Achat minimum de ' . number_format((float) $coupon->minimum_purchase, 2) . ' requis.',
            ];
        }

        $applicableSubtotal = $this->getApplicableSubtotal($cartItems, $coupon);
        if ($applicableSubtotal <= 0) {
            return [
                'valid' => false,
                'discount_amount' => 0,
                'message' => 'Ce code ne s\'applique pas aux produits de votre panier.',
            ];
        }

        $discountAmount = $this->calculateDiscount($coupon, $applicableSubtotal, $cartSubtotal);
        if ($discountAmount <= 0) {
            return ['valid' => false, 'discount_amount' => 0, 'message' => 'Réduction non applicable.'];
        }

        return [
            'valid' => true,
            'discount_amount' => $discountAmount,
            'coupon_id' => $coupon->id,
            'coupon_name' => $coupon->name,
        ];
    }

    private function getApplicableSubtotal(array $cartItems, CouponModel $coupon): float
    {
        $applicableProducts = $coupon->applicable_products;
        $applicableCategories = $coupon->applicable_categories;
        $excludedProducts = $coupon->excluded_products ?? [];

        if (empty($applicableProducts) && empty($applicableCategories)) {
            foreach ($excludedProducts as $pid) {
                if (empty($pid)) continue;
                $cartItems = array_filter($cartItems, fn ($i) => (string) ($i['product_id'] ?? '') !== (string) $pid);
            }
            return array_reduce($cartItems, fn ($sum, $i) => $sum + ($i['unit_price'] ?? 0) * ($i['quantity'] ?? 1), 0);
        }

        $productIds = $this->resolveApplicableProductIds($applicableProducts, $applicableCategories);
        if (empty($productIds)) {
            return 0;
        }

        $sum = 0;
        foreach ($cartItems as $item) {
            $pid = (string) ($item['product_id'] ?? '');
            if (in_array($pid, $excludedProducts, true)) {
                continue;
            }
            if (in_array($pid, $productIds, true)) {
                $sum += ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1);
            }
        }
        return $sum;
    }

    private function resolveApplicableProductIds(?array $productIds, ?array $categoryIds): array
    {
        $ids = [];
        if (!empty($productIds)) {
            $ids = array_map('strval', $productIds);
        }
        if (!empty($categoryIds)) {
            $fromCategories = \Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel::whereIn('category_id', $categoryIds)
                ->whereNotNull('category_id')
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
            $ids = array_values(array_unique(array_merge($ids, $fromCategories)));
        }
        return $ids;
    }

    private function calculateDiscount(CouponModel $coupon, float $applicableSubtotal, float $cartSubtotal): float
    {
        $value = (float) $coupon->discount_value;

        switch ($coupon->type) {
            case 'percentage':
                return round($applicableSubtotal * ($value / 100), 2);
            case 'fixed_amount':
                return min($value, $applicableSubtotal);
            case 'free_shipping':
                return 0;
            default:
                return 0;
        }
    }
}
