<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\OrderItemModel;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

/**
 * Page "paiement réussi" pour produits digitaux (vitrine).
 * Route: GET /ecommerce/payment/success/{token}
 */
class PaymentSuccessController
{
    public function show(Request $request, string $token): Response
    {
        $item = OrderItemModel::where('download_token', $token)->first();

        if (!$item) {
            abort(404, 'Lien invalide.');
        }

        if ($item->download_expires_at && $item->download_expires_at->isPast()) {
            abort(410, 'Ce lien a expiré.');
        }

        $order = OrderModel::find($item->order_id);
        if (!$order || $order->payment_status !== 'paid') {
            abort(403, 'Paiement non confirmé.');
        }

        $orderItems = OrderItemModel::where('order_id', $order->id)->get();
        $productsById = ProductModel::whereIn('id', $orderItems->pluck('product_id')->values()->toArray())->get()->keyBy('id');

        $digitalItems = [];
        $hasPhysical = false;

        foreach ($orderItems as $oi) {
            $product = $productsById[$oi->product_id] ?? null;
            $typeProduit = $product ? $product->type_produit : null;
            $productType = $product ? ($product->product_type ?? 'physical') : 'physical';
            $isDigital = $typeProduit === 'numerique' || $productType === 'digital';

            if ($isDigital && $oi->download_token) {
                $digitalItems[] = [
                    'id' => (string) $oi->id,
                    'product_name' => $product ? $product->name : $oi->product_name,
                    'download_url' => route('ecommerce.download', ['token' => $oi->download_token]),
                    'expires_at' => $oi->download_expires_at?->toIso8601String(),
                ];
            }

            if (!$isDigital) {
                $hasPhysical = true;
            }
        }

        return Inertia::render('Ecommerce/PaymentSuccess', [
            'title' => 'Paiement réussi',
            'order' => [
                'id' => (string) $order->id,
                'order_number' => (string) ($order->order_number ?? ''),
            ],
            'digital_items' => $digitalItems,
            'has_physical_items' => $hasPhysical,
            'support' => [
                'message' => 'Si vous rencontrez un problème, veuillez contacter l’administrateur.',
            ],
        ]);
    }
}

