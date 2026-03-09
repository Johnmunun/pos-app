<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Infrastructure\Ecommerce\Services\CouponValidationService;
use Src\Infrastructure\Ecommerce\Services\ShippingCostService;

class CheckoutController
{
    public function __construct(
        private readonly ShippingCostService $shippingCostService,
        private readonly CouponValidationService $couponValidationService
    ) {
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) abort(403, 'User not authenticated.');
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;
        if (!$shopId && !$isRoot) abort(403, 'Shop ID not found.');
        if ($isRoot && !$shopId) abort(403, 'Please select a shop first.');
        return (string) $shopId;
    }

    /**
     * Calcule les frais de livraison pour une méthode donnée.
     */
    public function calculateShipping(Request $request): JsonResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'shipping_method_id' => 'required|string',
            'cart_subtotal' => 'required|numeric|min:0',
            'cart_weight' => 'nullable|numeric|min:0',
        ]);

        $result = $this->shippingCostService->calculate(
            $validated['shipping_method_id'],
            (float) $validated['cart_subtotal'],
            (float) ($validated['cart_weight'] ?? 0),
            $shopId
        );

        if (!$result) {
            return response()->json(['success' => false, 'message' => 'Méthode de livraison introuvable.'], 400);
        }

        return response()->json([
            'success' => true,
            'shipping_amount' => $result['amount'],
            'method_name' => $result['method_name'],
            'method_id' => $result['method_id'],
        ]);
    }

    /**
     * Valide un code promo et retourne la réduction applicable.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'cart_subtotal' => 'required|numeric|min:0',
            'cart_items' => 'required|array',
            'cart_items.*.product_id' => 'required|string',
            'cart_items.*.quantity' => 'required|numeric|min:0',
            'cart_items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $result = $this->couponValidationService->validate(
            $validated['code'],
            $shopId,
            (float) $validated['cart_subtotal'],
            $validated['cart_items']
        );

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Code invalide.',
                'discount_amount' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'discount_amount' => $result['discount_amount'],
            'coupon_name' => $result['coupon_name'] ?? null,
            'coupon_id' => $result['coupon_id'] ?? null,
        ]);
    }
}
