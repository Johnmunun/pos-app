<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Pharmacy\DTO\SetSupplierProductPriceDTO;
use Src\Application\Pharmacy\UseCases\SupplierPricing\SetSupplierProductPriceUseCase;
use Src\Application\Pharmacy\UseCases\SupplierPricing\GetSupplierProductPriceUseCase;
use Src\Infrastructure\Pharmacy\Models\SupplierProductPriceModel;
use Src\Infrastructure\Pharmacy\Models\SupplierModel;
use Src\Infrastructure\Pharmacy\Models\ProductModel;

/**
 * Controller: SupplierPricingController
 *
 * Gère les prix fournisseur-produit.
 */
class SupplierPricingController extends Controller
{
    public function __construct(
        private readonly SetSupplierProductPriceUseCase $setSupplierProductPriceUseCase,
        private readonly GetSupplierProductPriceUseCase $getSupplierProductPriceUseCase
    ) {
    }

    /**
     * Liste les prix d'un fournisseur.
     */
    public function index(Request $request, string $supplierId): JsonResponse
    {
        $user = $request->user();
        $shopId = $user->shop_id ?? $user->tenant_id;

        // Vérifier que le fournisseur appartient à la boutique
        $supplier = SupplierModel::find($supplierId);
        if (!$supplier || $supplier->shop_id != $shopId) {
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur introuvable.',
            ], 404);
        }

        $prices = SupplierProductPriceModel::query()
            ->where('supplier_id', $supplierId)
            ->where('is_active', true)
            ->with('product:id,name,code')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($p) {
                /** @var \Src\Infrastructure\Pharmacy\Models\ProductModel|null $product */
                $product = $p->product;
                return [
                    'id' => $p->id,
                    'product_id' => $p->product_id,
                    'product_name' => $product !== null ? $product->name : '',
                    'product_code' => $product !== null ? ($product->code ?? '') : '',
                    'normal_price' => $p->normal_price,
                    'agreed_price' => $p->agreed_price,
                    'effective_price' => $p->effective_price,
                    'tax_rate' => $p->tax_rate,
                    'price_with_tax' => $p->price_with_tax,
                    'effective_from' => $p->effective_from !== null ? $p->effective_from->format('Y-m-d') : null,
                ];
            });

        return response()->json([
            'success' => true,
            'prices' => $prices,
        ]);
    }

    /**
     * Définit ou met à jour un prix.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|string',
            'product_id' => 'required|string',
            'normal_price' => 'required|numeric|min:0',
            'agreed_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'effective_from' => 'nullable|date',
        ]);

        $user = $request->user();
        $shopId = $user->shop_id ?? $user->tenant_id;

        // Vérifier que le fournisseur appartient à la boutique
        $supplier = SupplierModel::find($validated['supplier_id']);
        if (!$supplier || $supplier->shop_id != $shopId) {
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur introuvable.',
            ], 404);
        }

        // Vérifier que le produit appartient à la boutique
        $product = ProductModel::find($validated['product_id']);
        if (!$product || $product->shop_id != $shopId) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable.',
            ], 404);
        }

        try {
            $dto = new SetSupplierProductPriceDTO(
                supplierId: $validated['supplier_id'],
                productId: $validated['product_id'],
                normalPrice: (float) $validated['normal_price'],
                agreedPrice: isset($validated['agreed_price']) ? (float) $validated['agreed_price'] : null,
                taxRate: (float) ($validated['tax_rate'] ?? 0),
                effectiveFrom: $validated['effective_from'] ?? null
            );

            $price = $this->setSupplierProductPriceUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Prix défini avec succès.',
                'price' => [
                    'id' => $price->getId(),
                    'effective_price' => $price->getEffectivePrice(),
                    'price_with_tax' => $price->getEffectivePriceWithTax(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur définition prix fournisseur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la définition du prix.',
            ], 500);
        }
    }

    /**
     * Récupère le prix d'un produit pour un fournisseur.
     */
    public function getPrice(Request $request, string $supplierId, string $productId): JsonResponse
    {
        $price = $this->getSupplierProductPriceUseCase->execute($supplierId, $productId);

        if ($price === null) {
            return response()->json([
                'success' => true,
                'price' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'price' => [
                'id' => $price->getId(),
                'normal_price' => $price->getNormalPrice(),
                'agreed_price' => $price->getAgreedPrice(),
                'effective_price' => $price->getEffectivePrice(),
                'tax_rate' => $price->getTaxRate()->getValue(),
                'price_with_tax' => $price->getEffectivePriceWithTax(),
                'effective_from' => $price->getEffectiveFrom()->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Récupère tous les prix pour un produit (tous fournisseurs).
     */
    public function getProductPrices(Request $request, string $productId): JsonResponse
    {
        $user = $request->user();
        $shopId = $user->shop_id ?? $user->tenant_id;

        // Vérifier que le produit appartient à la boutique
        $product = ProductModel::find($productId);
        if (!$product || $product->shop_id != $shopId) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable.',
            ], 404);
        }

        $prices = SupplierProductPriceModel::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->with('supplier:id,name')
            ->orderBy('effective_from', 'desc')
            ->get()
            ->map(function ($p) {
                /** @var \Src\Infrastructure\Pharmacy\Models\SupplierModel|null $supplier */
                $supplier = $p->supplier;
                return [
                    'id' => $p->id,
                    'supplier_id' => $p->supplier_id,
                    'supplier_name' => $supplier !== null ? $supplier->name : '',
                    'normal_price' => $p->normal_price,
                    'agreed_price' => $p->agreed_price,
                    'effective_price' => $p->effective_price,
                    'tax_rate' => $p->tax_rate,
                    'price_with_tax' => $p->price_with_tax,
                    'effective_from' => $p->effective_from !== null ? $p->effective_from->format('Y-m-d') : null,
                ];
            });

        return response()->json([
            'success' => true,
            'prices' => $prices,
        ]);
    }

    /**
     * Supprime un prix.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $price = SupplierProductPriceModel::find($id);

        if (!$price) {
            return response()->json([
                'success' => false,
                'message' => 'Prix introuvable.',
            ], 404);
        }

        $price->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Prix supprimé avec succès.',
        ]);
    }
}
