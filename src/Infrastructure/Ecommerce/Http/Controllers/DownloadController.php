<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;
use Src\Infrastructure\Ecommerce\Models\OrderItemModel;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

/**
 * Téléchargement sécurisé des produits digitaux.
 * Route: GET /ecommerce/download/{token}
 */
class DownloadController
{
    public function __invoke(Request $request, string $token): Response|\Illuminate\Http\RedirectResponse|BinaryFileResponse
    {
        $item = OrderItemModel::where('download_token', $token)->first();

        if (!$item) {
            abort(404, 'Lien de téléchargement invalide ou expiré.');
        }

        if ($item->download_expires_at && $item->download_expires_at->isPast()) {
            abort(410, 'Ce lien de téléchargement a expiré.');
        }

        $order = OrderModel::find($item->order_id);
        if (!$order || $order->payment_status !== 'paid') {
            abort(403, 'Cette commande n\'est pas encore payée.');
        }

        $product = ProductModel::find($item->product_id);
        $typeProduit = $product->type_produit ?? null;
        $productType = $product->product_type ?? 'physical';
        $isDigital = $typeProduit === 'numerique' || $productType === 'digital';
        if (!$product || !$isDigital) {
            abort(404, 'Produit non disponible en téléchargement.');
        }

        // URL externe (lien_telechargement - prioritaire pour produits numériques e-commerce)
        if (!empty($product->lien_telechargement)) {
            return redirect()->away($product->lien_telechargement);
        }

        // Fichier stocké localement (download_path)
        if (!empty($product->download_path)) {
            $path = $product->download_path;
            if (Storage::disk('public')->exists($path)) {
                return response()->file(Storage::disk('public')->path($path), [
                    'Content-Disposition' => 'attachment; filename="' . basename($path) . '"',
                ]);
            }
            abort(404, 'Fichier introuvable.');
        }

        // URL externe (download_url) — redirection (fallback)
        if (!empty($product->download_url)) {
            return redirect()->away($product->download_url);
        }

        abort(404, 'Aucun fichier de téléchargement configuré pour ce produit.');
    }
}
