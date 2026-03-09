<?php

namespace Src\Application\Ecommerce\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Infrastructure\Ecommerce\Models\OrderItemModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

/**
 * Génère les tokens de téléchargement pour les produits digitaux
 * quand une commande est marquée comme payée.
 */
class GenerateDownloadTokensService
{
    private const TOKEN_EXPIRY_DAYS = 7;

    public function generateForOrder(string $orderId): void
    {
        $items = OrderItemModel::where('order_id', $orderId)->get();

        foreach ($items as $item) {
            $product = ProductModel::find($item->product_id);
            if (!$product) {
                continue;
            }

            $productType = $product->product_type ?? 'physical';
            if ($productType !== 'digital') {
                continue;
            }

            $hasDownload = !empty($product->download_url) || !empty($product->download_path);
            if (!$hasDownload) {
                continue;
            }

            OrderItemModel::where('id', $item->id)->update([
                'is_digital' => true,
                'download_token' => Str::random(64),
                'download_expires_at' => now()->addDays(self::TOKEN_EXPIRY_DAYS),
            ]);
        }
    }
}
