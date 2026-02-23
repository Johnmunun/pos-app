<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Src\Domain\Pharmacy\Repositories\StockMovementRepositoryInterface;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Services\PharmacyExportService;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller pour la gestion des mouvements de stock des produits
 * 
 * Fournit des endpoints pour :
 * - Lister les mouvements avec filtres
 * - Exporter en PDF (individuel et global)
 */
class ProductMovementController
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
        private readonly PharmacyExportService $exportService
    ) {}

    /**
     * Récupère le shop ID de l'utilisateur courant
     */
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        
        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($user->id);
        $isRoot = $userModel !== null && $userModel->isRoot();
        
        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }
        
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }
        
        return (string) $shopId;
    }

    /**
     * Liste les mouvements de stock avec filtres
     * 
     * GET /api/pharmacy/product-movements
     */
    public function index(Request $request): JsonResponse
    {
        $shopId = $this->getShopId($request);

        $filters = [
            'product_id' => $request->input('product_id'),
            'product_code' => $request->input('product_code'),
            'product_name' => $request->input('product_name'),
            'type' => $request->input('type'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        // Nettoyer les filtres vides
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

        $movements = $this->stockMovementRepository->findByShopWithFilters($shopId, $filters);
        $count = count($movements);

        // Enrichir les mouvements avec les données produit et utilisateur
        $movementsData = [];
        foreach ($movements as $movement) {
            /** @var ProductModel|null $product */
            $product = ProductModel::query()->find($movement->getProductId());
            
            /** @var UserModel|null $creator */
            $creator = UserModel::query()->find($movement->getCreatedBy());

            $productName = $product !== null ? $product->name : 'Produit inconnu';
            $productCode = $product !== null ? ($product->code ?? '') : '';
            $categoryName = ($product !== null && $product->category !== null) ? $product->category->name : '';
            $creatorName = $creator !== null ? $creator->name : 'Utilisateur inconnu';

            $movementsData[] = [
                'id' => $movement->getId(),
                'product_id' => $movement->getProductId(),
                'product_name' => $productName,
                'product_code' => $productCode,
                'category_name' => $categoryName,
                'type' => $movement->getType(),
                'quantity' => $movement->getQuantity()->getValue(),
                'reference' => $movement->getReference(),
                'created_by' => $movement->getCreatedBy(),
                'created_by_name' => $creatorName,
                'created_at' => $movement->getCreatedAt()->format('Y-m-d H:i:s'),
                'created_at_formatted' => $movement->getCreatedAt()->format('d/m/Y H:i'),
            ];
        }

        // Calcul des statistiques
        $stats = [
            'total_movements' => $count,
            'total_in' => 0,
            'total_out' => 0,
            'total_adjustment' => 0,
        ];

        foreach ($movements as $m) {
            switch ($m->getType()) {
                case 'IN':
                    $stats['total_in'] += $m->getQuantity()->getValue();
                    break;
                case 'OUT':
                    $stats['total_out'] += $m->getQuantity()->getValue();
                    break;
                case 'ADJUSTMENT':
                    $stats['total_adjustment'] += $m->getQuantity()->getValue();
                    break;
            }
        }

        return response()->json([
            'movements' => $movementsData,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Exporte un mouvement individuel en PDF
     * 
     * GET /api/pharmacy/product-movements/{id}/pdf
     */
    public function exportSinglePdf(Request $request, string $id): Response
    {
        $shopId = $this->getShopId($request);
        
        $movement = $this->stockMovementRepository->findById($id, $shopId);
        
        if ($movement === null) {
            abort(404, 'Mouvement non trouvé');
        }

        /** @var ProductModel|null $product */
        $product = ProductModel::query()->find($movement->getProductId());
        
        /** @var UserModel|null $creator */
        $creator = UserModel::query()->find($movement->getCreatedBy());

        $header = $this->exportService->getExportHeader($request);

        $creatorName = $creator !== null ? $creator->name : 'Utilisateur inconnu';
        $productId = $product !== null ? $product->id : '';
        $productName = $product !== null ? $product->name : 'Produit inconnu';
        $productCode = $product !== null ? ($product->code ?? '') : '';
        $productCategory = ($product !== null && $product->category !== null) ? $product->category->name : '';
        $productStock = $product !== null ? ($product->stock ?? 0) : 0;
        $productPrice = $product !== null ? ($product->price_amount ?? 0) : 0;

        $data = [
            'header' => $header,
            'movement' => [
                'id' => $movement->getId(),
                'type' => $movement->getType(),
                'type_label' => $this->getTypeLabel($movement->getType()),
                'quantity' => $movement->getQuantity()->getValue(),
                'reference' => $movement->getReference(),
                'created_at' => $movement->getCreatedAt()->format('d/m/Y H:i'),
                'created_by_name' => $creatorName,
            ],
            'product' => [
                'id' => $productId,
                'name' => $productName,
                'code' => $productCode,
                'category' => $productCategory,
                'current_stock' => $productStock,
                'price' => $productPrice,
            ],
        ];

        $pdf = Pdf::loadView('pharmacy.exports.movement-single', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'mouvement_' . substr($id, 0, 8) . '_' . now()->format('Ymd_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Exporte les mouvements globaux en PDF
     * 
     * GET /api/pharmacy/product-movements/export/pdf
     */
    public function exportGlobalPdf(Request $request): Response
    {
        $shopId = $this->getShopId($request);

        $filters = [
            'product_id' => $request->input('product_id'),
            'product_code' => $request->input('product_code'),
            'product_name' => $request->input('product_name'),
            'type' => $request->input('type'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

        $movements = $this->stockMovementRepository->findByShopWithFilters($shopId, $filters);

        // Grouper par catégorie puis par produit
        $groupedData = [];
        $totals = [
            'total_in' => 0,
            'total_out' => 0,
            'total_adjustment' => 0,
            'total_movements' => count($movements),
        ];

        foreach ($movements as $movement) {
            /** @var ProductModel|null $product */
            $product = ProductModel::query()
                ->with('category')
                ->find($movement->getProductId());
            
            /** @var UserModel|null $creator */
            $creator = UserModel::query()->find($movement->getCreatedBy());

            $categoryName = ($product !== null && $product->category !== null) ? $product->category->name : 'Sans catégorie';
            $productId = $movement->getProductId();
            $productName = $product !== null ? $product->name : 'Produit inconnu';
            $productCode = $product !== null ? ($product->code ?? '') : '';
            $creatorName = $creator !== null ? $creator->name : 'Utilisateur inconnu';

            if (!isset($groupedData[$categoryName])) {
                $groupedData[$categoryName] = [
                    'name' => $categoryName,
                    'products' => [],
                    'totals' => ['in' => 0, 'out' => 0, 'adjustment' => 0],
                ];
            }

            if (!isset($groupedData[$categoryName]['products'][$productId])) {
                $groupedData[$categoryName]['products'][$productId] = [
                    'id' => $productId,
                    'name' => $productName,
                    'code' => $productCode,
                    'movements' => [],
                    'totals' => ['in' => 0, 'out' => 0, 'adjustment' => 0],
                ];
            }

            $qty = $movement->getQuantity()->getValue();
            $type = $movement->getType();

            $groupedData[$categoryName]['products'][$productId]['movements'][] = [
                'id' => $movement->getId(),
                'type' => $type,
                'type_label' => $this->getTypeLabel($type),
                'quantity' => $qty,
                'reference' => $movement->getReference(),
                'created_at' => $movement->getCreatedAt()->format('d/m/Y H:i'),
                'created_by' => $creatorName,
            ];

            // Mise à jour des totaux
            switch ($type) {
                case 'IN':
                    $groupedData[$categoryName]['products'][$productId]['totals']['in'] += $qty;
                    $groupedData[$categoryName]['totals']['in'] += $qty;
                    $totals['total_in'] += $qty;
                    break;
                case 'OUT':
                    $groupedData[$categoryName]['products'][$productId]['totals']['out'] += $qty;
                    $groupedData[$categoryName]['totals']['out'] += $qty;
                    $totals['total_out'] += $qty;
                    break;
                case 'ADJUSTMENT':
                    $groupedData[$categoryName]['products'][$productId]['totals']['adjustment'] += $qty;
                    $groupedData[$categoryName]['totals']['adjustment'] += $qty;
                    $totals['total_adjustment'] += $qty;
                    break;
            }
        }

        // Convertir les produits en arrays indexés
        /** @var array<string, array{name: string, products: array<string, mixed>, totals: array{in: int, out: int, adjustment: int}}> $groupedData */
        foreach ($groupedData as $key => $category) {
            /** @var array<string, mixed> $products */
            $products = $category['products'];
            $groupedData[$key]['products'] = array_values($products);
        }

        $header = $this->exportService->getExportHeader($request);

        /** @var array<int, array{name: string, products: array<int, mixed>, totals: array{in: int, out: int, adjustment: int}}> $categoriesIndexed */
        $categoriesIndexed = array_values($groupedData);

        $data = [
            'header' => $header,
            'categories' => $categoriesIndexed,
            'totals' => $totals,
            'filters' => [
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
                'product_name' => $filters['product_name'] ?? null,
                'product_code' => $filters['product_code'] ?? null,
                'type' => isset($filters['type']) ? $this->getTypeLabel($filters['type']) : null,
            ],
        ];

        $pdf = Pdf::loadView('pharmacy.exports.movements-global', $data);
        $pdf->setPaper('a4', 'landscape');

        $filename = 'mouvements_stock_' . now()->format('Ymd_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Retourne le libellé français du type de mouvement
     */
    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'IN' => 'Entrée',
            'OUT' => 'Sortie',
            'ADJUSTMENT' => 'Ajustement',
            'RETURN' => 'Retour',
            default => $type,
        };
    }
}
