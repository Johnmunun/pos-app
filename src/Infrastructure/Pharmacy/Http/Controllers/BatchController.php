<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;
use Src\Application\Pharmacy\DTO\CreateBatchDTO;
use Src\Application\Pharmacy\UseCases\Batch\AddBatchUseCase;
use Src\Application\Pharmacy\UseCases\Batch\GetExpiredBatchesUseCase;
use Src\Application\Pharmacy\UseCases\Batch\GetExpiringBatchesUseCase;
use Src\Application\Pharmacy\UseCases\Batch\GetBatchSummaryUseCase;
use Src\Application\Pharmacy\UseCases\Batch\ListBatchesUseCase;
use Src\Domain\Pharmacy\Repositories\ProductBatchRepositoryInterface;
use Src\Infrastructure\Pharmacy\Models\ProductModel;

/**
 * Controller for batch and expiration management.
 */
class BatchController
{
    public function __construct(
        private AddBatchUseCase $addBatchUseCase,
        private GetExpiredBatchesUseCase $getExpiredBatchesUseCase,
        private GetExpiringBatchesUseCase $getExpiringBatchesUseCase,
        private GetBatchSummaryUseCase $getBatchSummaryUseCase,
        private ListBatchesUseCase $listBatchesUseCase,
        private ProductBatchRepositoryInterface $batchRepository
    ) {}

    /**
     * Get shop ID from authenticated user.
     */
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = null;
        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shopByDepot = \App\Models\Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first();
            if ($shopByDepot) {
                $shopId = (string) $shopByDepot->id;
            }
        }
        if ($shopId === null) {
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        }
        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;
        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }
        return (string) $shopId;
    }

    /**
     * Expirations page - List expired and expiring batches.
     */
    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $warningDays = (int) ($request->input('warning_days', 30));

        // Get filters
        $filters = [
            'shop_id' => $shopId,
            'product_id' => $request->input('product_id'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];

        // Get batches with pagination
        $perPage = (int) ($request->input('per_page', 20));
        $page = (int) ($request->input('page', 1));
        $offset = ($page - 1) * $perPage;

        $result = $this->listBatchesUseCase->execute($filters, $perPage, $offset);
        
        // Get summary for dashboard
        $summary = $this->getBatchSummaryUseCase->execute($shopId, $warningDays);

        // Get products for filter dropdown
        $products = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
            ])
            ->toArray();

        // Format batches for frontend
        $batches = array_map(function ($batch) use ($warningDays) {
            $product = ProductModel::find($batch->getProductId());
            return [
                'id' => $batch->getId(),
                'product_id' => $batch->getProductId(),
                'product_name' => $product ? $product->name : '',
                'product_code' => $product ? $product->code : '',
                'batch_number' => $batch->getBatchNumber()->getValue(),
                'quantity' => $batch->getQuantity()->getValue(),
                'expiration_date' => $batch->getExpirationDate()->format('Y-m-d'),
                'days_until_expiration' => $batch->getDaysUntilExpiration(),
                'status' => $batch->getExpirationStatus($warningDays),
                'created_at' => $batch->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }, $result['batches']);

        // Calculate pagination
        $totalPages = (int) ceil($result['total'] / $perPage);

        return Inertia::render('Pharmacy/Expirations/Index', [
            'batches' => [
                'data' => $batches,
                'current_page' => $page,
                'last_page' => $totalPages,
                'per_page' => $perPage,
                'total' => $result['total'],
            ],
            'summary' => $summary,
            'products' => $products,
            'filters' => [
                'product_id' => $request->input('product_id'),
                'status' => $request->input('status'),
                'search' => $request->input('search'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'warning_days' => $warningDays,
            ],
        ]);
    }

    /**
     * Get batch summary for dashboard.
     */
    public function summary(Request $request): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $warningDays = (int) ($request->input('warning_days', 30));

        try {
            $summary = $this->getBatchSummaryUseCase->execute($shopId, $warningDays);
            
            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting batch summary', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du résumé.',
            ], 500);
        }
    }

    /**
     * Store a new batch.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|string',
            'batch_number' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1',
            'expiration_date' => 'required|date|after:today',
            'purchase_order_id' => 'nullable|string',
            'purchase_order_line_id' => 'nullable|string',
        ]);

        $shopId = $this->getShopId($request);

        // Verify product belongs to shop
        $product = ProductModel::where('id', $validated['product_id'])
            ->where('shop_id', $shopId)
            ->first();
            
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé.',
            ], 404);
        }

        try {
            $dto = new CreateBatchDTO(
                shopId: $shopId,
                productId: $validated['product_id'],
                batchNumber: $validated['batch_number'],
                quantity: (int) $validated['quantity'],
                expirationDate: new DateTimeImmutable($validated['expiration_date']),
                purchaseOrderId: $validated['purchase_order_id'] ?? null,
                purchaseOrderLineId: $validated['purchase_order_line_id'] ?? null
            );

            $batch = $this->addBatchUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Lot créé avec succès.',
                'batch' => [
                    'id' => $batch->getId(),
                    'batch_number' => $batch->getBatchNumber()->getValue(),
                    'quantity' => $batch->getQuantity()->getValue(),
                    'expiration_date' => $batch->getExpirationDate()->format('Y-m-d'),
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating batch', [
                'shop_id' => $shopId,
                'product_id' => $validated['product_id'],
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du lot.',
            ], 500);
        }
    }

    /**
     * Get batches for a specific product.
     */
    public function getProductBatches(Request $request, string $productId): JsonResponse
    {
        $shopId = $this->getShopId($request);

        // Verify product belongs to shop
        $product = ProductModel::where('id', $productId)
            ->where('shop_id', $shopId)
            ->first();
            
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé.',
            ], 404);
        }

        try {
            $batches = $this->batchRepository->findByProduct($productId);
            
            $data = array_map(fn ($batch) => [
                'id' => $batch->getId(),
                'batch_number' => $batch->getBatchNumber()->getValue(),
                'quantity' => $batch->getQuantity()->getValue(),
                'expiration_date' => $batch->getExpirationDate()->format('Y-m-d'),
                'days_until_expiration' => $batch->getDaysUntilExpiration(),
                'status' => $batch->getExpirationStatus(),
            ], $batches);

            return response()->json([
                'success' => true,
                'batches' => $data,
                'total_stock' => $this->batchRepository->getTotalStockByProduct($productId),
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting product batches', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des lots.',
            ], 500);
        }
    }

    /**
     * Get expired batches.
     */
    public function expired(Request $request): JsonResponse
    {
        $shopId = $this->getShopId($request);

        try {
            $batches = $this->getExpiredBatchesUseCase->execute($shopId);
            
            $data = array_map(function ($batch) {
                $product = ProductModel::find($batch->getProductId());
                return [
                    'id' => $batch->getId(),
                    'product_id' => $batch->getProductId(),
                    'product_name' => $product ? $product->name : '',
                    'batch_number' => $batch->getBatchNumber()->getValue(),
                    'quantity' => $batch->getQuantity()->getValue(),
                    'expiration_date' => $batch->getExpirationDate()->format('Y-m-d'),
                    'days_expired' => abs($batch->getDaysUntilExpiration()),
                ];
            }, $batches);

            return response()->json([
                'success' => true,
                'batches' => $data,
                'count' => count($data),
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting expired batches', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des lots expirés.',
            ], 500);
        }
    }

    /**
     * Get expiring batches.
     */
    public function expiring(Request $request): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $days = (int) ($request->input('days', 30));

        try {
            $batches = $this->getExpiringBatchesUseCase->execute($shopId, $days);
            
            $data = array_map(function ($batch) {
                $product = ProductModel::find($batch->getProductId());
                return [
                    'id' => $batch->getId(),
                    'product_id' => $batch->getProductId(),
                    'product_name' => $product ? $product->name : '',
                    'batch_number' => $batch->getBatchNumber()->getValue(),
                    'quantity' => $batch->getQuantity()->getValue(),
                    'expiration_date' => $batch->getExpirationDate()->format('Y-m-d'),
                    'days_until_expiration' => $batch->getDaysUntilExpiration(),
                ];
            }, $batches);

            return response()->json([
                'success' => true,
                'batches' => $data,
                'count' => count($data),
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting expiring batches', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des lots bientôt expirés.',
            ], 500);
        }
    }

    /**
     * Delete a batch (deactivate).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);

        try {
            $batch = $this->batchRepository->findById($id);
            
            if (!$batch || $batch->getShopId() !== $shopId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lot non trouvé.',
                ], 404);
            }

            $batch->deactivate();
            $this->batchRepository->update($batch);

            return response()->json([
                'success' => true,
                'message' => 'Lot supprimé avec succès.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting batch', [
                'batch_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du lot.',
            ], 500);
        }
    }
}
