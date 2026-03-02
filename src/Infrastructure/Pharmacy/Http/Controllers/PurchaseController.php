<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;
use Src\Application\Pharmacy\UseCases\Purchases\CreatePurchaseOrderUseCase;
use Src\Application\Pharmacy\UseCases\Purchases\ConfirmPurchaseOrderUseCase;
use Src\Application\Pharmacy\UseCases\Purchases\ReceivePurchaseOrderUseCase;
use Src\Application\Pharmacy\UseCases\Purchases\CancelPurchaseOrderUseCase;
use Src\Domain\Pharmacy\Repositories\PurchaseOrderRepositoryInterface;
use Src\Application\Pharmacy\DTO\PurchaseOrderLineDTO;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Models\SupplierModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel as QuincaillerieProductModel;
use Src\Infrastructure\Quincaillerie\Models\SupplierModel as QuincaillerieSupplierModel;
use Src\Application\Quincaillerie\Services\DepotFilterService;
use Src\Infrastructure\Pharmacy\Adapters\QuincaillerieProductRepositoryAdapter;
use Src\Infrastructure\Pharmacy\Adapters\NoOpAddBatchUseCase;
use Src\Infrastructure\Pharmacy\Adapters\HardwareUpdateStockUseCase;
use Src\Domain\Quincaillerie\Repositories\ProductRepositoryInterface as QuincaillerieProductRepositoryInterface;
use Src\Infrastructure\Pharmacy\Services\PharmacyExportService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PurchaseController
{
    private function getModule(): string
    {
        $prefix = request()->route()?->getPrefix();
        // Le prefix peut être '/hardware' ou 'hardware', donc on normalise
        $normalizedPrefix = $prefix ? trim($prefix, '/') : '';
        return $normalizedPrefix === 'hardware' ? 'Hardware' : 'Pharmacy';
    }

    public function __construct(
        private CreatePurchaseOrderUseCase $createPurchaseOrderUseCase,
        private ConfirmPurchaseOrderUseCase $confirmPurchaseOrderUseCase,
        private ReceivePurchaseOrderUseCase $receivePurchaseOrderUseCase,
        private CancelPurchaseOrderUseCase $cancelPurchaseOrderUseCase,
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private PharmacyExportService $exportService
    ) {}
    
    /**
     * Récupère le DepotFilterService si disponible (uniquement pour Hardware)
     */
    private function getDepotFilterService(): ?DepotFilterService
    {
        if ($this->getModule() === 'Hardware') {
            return app(DepotFilterService::class);
        }
        return null;
    }

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
        $userModel = UserModel::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;
        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $status = $request->input('status');
        $from = $request->filled('from') ? new \DateTimeImmutable($request->input('from')) : null;
        $to = $request->filled('to') ? new \DateTimeImmutable($request->input('to')) : null;

        Log::info('PurchaseController::index - Début', [
            'shop_id' => $shopId,
            'status' => $status,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
            'route_name' => $request->route()?->getName(),
            'route_prefix' => $request->route()?->getPrefix(),
            'url' => $request->url(),
        ]);

        $pos = $this->purchaseOrderRepository->findByShop($shopId, $status, $from, $to);
        $isHardware = $this->getModule() === 'Hardware';
        
        Log::info('PurchaseController::index - Module détecté', [
            'module' => $this->getModule(),
            'is_hardware' => $isHardware,
            'purchase_orders_count' => count($pos),
        ]);
        $list = [];
        foreach ($pos as $po) {
            $supplier = $isHardware 
                ? QuincaillerieSupplierModel::find($po->getSupplierId())
                : SupplierModel::find($po->getSupplierId());
            $list[] = [
                'id' => $po->getId(),
                'status' => $po->getStatus(),
                'total_amount' => $po->getTotal()->getAmount(),
                'currency' => $po->getCurrency(),
                'supplier_id' => $po->getSupplierId(),
                'supplier_name' => $supplier ? $supplier->name : '',
                'ordered_at' => $po->getOrderedAt() ? $po->getOrderedAt()->format('Y-m-d') : null,
                'expected_at' => $po->getExpectedAt() ? $po->getExpectedAt()->format('Y-m-d') : null,
                'created_at' => $po->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }

        // Fetch suppliers and products for the drawer
        $isHardware = $this->getModule() === 'Hardware';
        
        if ($isHardware) {
            // Les fournisseurs ne sont pas filtrés par dépôt car un fournisseur peut servir plusieurs dépôts
            $suppliersQuery = QuincaillerieSupplierModel::query()
                ->where('shop_id', $shopId)
                ->where('status', 'active');
            
            /** @var \Illuminate\Database\Eloquent\Collection<int, QuincaillerieSupplierModel> $suppliersCollection */
            $suppliersCollection = $suppliersQuery->orderBy('name')->get();
            $suppliers = $suppliersCollection->map(function (QuincaillerieSupplierModel $s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'phone' => $s->phone,
                ];
            })->toArray();

            $productsQuery = QuincaillerieProductModel::where('shop_id', $shopId)
                ->where('is_active', true);
            
            // Appliquer le filtrage par dépôt pour Hardware
            $depotFilterService = $this->getDepotFilterService();
            if ($depotFilterService !== null) {
                $productsQuery = $depotFilterService->applyDepotFilter($productsQuery, $request, 'depot_id');
            }
            
            /** @var \Illuminate\Database\Eloquent\Collection<int, QuincaillerieProductModel> $productsCollection */
            $productsCollection = $productsQuery->orderBy('name')->get();
            $products = $productsCollection->map(function (QuincaillerieProductModel $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code ?? '',
                    'cost_amount' => isset($p->cost_amount) ? (float) $p->cost_amount : 0,
                ];
            })->toArray();
        } else {
            /** @var \Illuminate\Database\Eloquent\Collection<int, SupplierModel> $suppliersCollection */
            $suppliersCollection = SupplierModel::query()
                ->where('shop_id', $shopId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
            $suppliers = $suppliersCollection->map(function (SupplierModel $s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'phone' => $s->phone,
                ];
            })->toArray();

            /** @var \Illuminate\Database\Eloquent\Collection<int, ProductModel> $productsCollection */
            $productsCollection = ProductModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            $products = $productsCollection->map(function (ProductModel $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code ?? '',
                    'cost_amount' => isset($p->cost_amount) ? (float) $p->cost_amount : 0,
                ];
            })->toArray();
        }

        $module = $this->getModule();
        $viewPath = $module . '/Purchases/Index';
        
        Log::info('PurchaseController::index - Données envoyées au frontend', [
            'module' => $module,
            'view_path' => $viewPath,
            'purchase_orders_count' => count($list),
            'suppliers_count' => count($suppliers),
            'products_count' => count($products),
        ]);

        return Inertia::render($viewPath, [
            'purchase_orders' => $list,
            'filters' => $request->only(['from', 'to', 'status']),
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $isHardware = $this->getModule() === 'Hardware';
        
        Log::info('PurchaseController::create - Debug', [
            'shop_id' => $shopId,
            'module' => $this->getModule(),
            'is_hardware' => $isHardware,
        ]);
        
        if ($isHardware) {
            $productsQuery = QuincaillerieProductModel::where('shop_id', $shopId)->where('is_active', true);
            
            // Appliquer le filtrage par dépôt pour Hardware
            $depotFilterService = $this->getDepotFilterService();
            if ($depotFilterService !== null) {
                $productsQuery = $depotFilterService->applyDepotFilter($productsQuery, $request, 'depot_id');
            }
            
            /** @var \Illuminate\Database\Eloquent\Collection<int, QuincaillerieProductModel> $productsCollection */
            $productsCollection = $productsQuery->orderBy('name')->get();
            $products = $productsCollection->map(function (QuincaillerieProductModel $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code ?? '',
                    'cost_amount' => isset($p->cost_amount) ? (float) $p->cost_amount : 0,
                ];
            })->toArray();

            // Les fournisseurs ne sont pas filtrés par dépôt car un fournisseur peut servir plusieurs dépôts
            $suppliersQuery = QuincaillerieSupplierModel::query()
                ->where('shop_id', $shopId)
                ->where('status', 'active');
            
            // Debug: Vérifier tous les fournisseurs avant filtrage
            $allSuppliers = QuincaillerieSupplierModel::where('shop_id', $shopId)->get();
            Log::info('PurchaseController::create - Tous les fournisseurs (avant filtrage status)', [
                'count' => $allSuppliers->count(),
                'suppliers' => $allSuppliers->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'status' => $s->status,
                    'shop_id' => $s->shop_id,
                    'depot_id' => $s->depot_id ?? null,
                ])->toArray(),
            ]);
            
            /** @var \Illuminate\Database\Eloquent\Collection<int, QuincaillerieSupplierModel> $suppliersCollection */
            $suppliersCollection = $suppliersQuery->orderBy('name')->get();
            
            Log::info('PurchaseController::create - Fournisseurs actifs (après filtrage)', [
                'count' => $suppliersCollection->count(),
                'suppliers' => $suppliersCollection->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'status' => $s->status,
                ])->toArray(),
            ]);
            
            $suppliers = $suppliersCollection->map(function (QuincaillerieSupplierModel $s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'phone' => $s->phone,
                ];
            })->toArray();
            
            Log::info('PurchaseController::create - Fournisseurs formatés pour le frontend', [
                'count' => count($suppliers),
                'suppliers' => $suppliers,
            ]);
        } else {
            $products = ProductModel::where('shop_id', $shopId)->where('is_active', true)->orderBy('name')->get()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code ?? '',
                'cost_amount' => isset($p->cost_amount) ? (float) $p->cost_amount : 0,
            ])->toArray();

            $suppliers = SupplierModel::query()->where('shop_id', $shopId)->where('status', 'active')->orderBy('name')->get()->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone,
            ])->toArray();
        }

        Log::info('PurchaseController::create - Données envoyées au frontend', [
            'suppliers_count' => count($suppliers),
            'products_count' => count($products),
            'module' => $this->getModule(),
        ]);

        return Inertia::render($this->getModule() . '/Purchases/Create', [
            'products' => $products,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'required|string',
            'currency' => 'required|string|size:3',
            'expected_at' => 'nullable|date',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|string',
            'lines.*.ordered_quantity' => 'required|integer|min:1',
            'lines.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $shopId = $this->getShopId($request);
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $userId = (int) $user->id;
        $expectedAt = $request->filled('expected_at') ? new \DateTimeImmutable($request->input('expected_at')) : null;
        $lines = [];
        foreach ($request->input('lines') as $row) {
            $lines[] = new PurchaseOrderLineDTO(
                $row['product_id'],
                (int) $row['ordered_quantity'],
                (float) $row['unit_cost']
            );
        }

        try {
            $isHardware = $this->getModule() === 'Hardware';
            
            // Pour Hardware, vérifier que tous les produits existent dans Quincaillerie
            if ($isHardware) {
                foreach ($lines as $lineDto) {
                    $product = QuincaillerieProductModel::where('id', $lineDto->productId)
                        ->where('shop_id', $shopId)
                        ->where('is_active', true)
                        ->first();
                    
                    if (!$product) {
                        Log::error('PurchaseController::store - Produit Hardware non trouvé', [
                            'product_id' => $lineDto->productId,
                            'shop_id' => $shopId,
                        ]);
                        return response()->json([
                            'message' => "Product not found for purchase order line (ID: {$lineDto->productId})"
                        ], 422);
                    }
                }
                
                // Utiliser l'adapter pour Hardware (crée une entité Pharmacy minimale juste pour la vérification)
                $quincaillerieProductRepository = app(QuincaillerieProductRepositoryInterface::class);
                $adapter = new QuincaillerieProductRepositoryAdapter($quincaillerieProductRepository);
                
                $createUseCase = new CreatePurchaseOrderUseCase(
                    $this->purchaseOrderRepository,
                    app(\Src\Domain\Pharmacy\Repositories\PurchaseOrderLineRepositoryInterface::class),
                    $adapter
                );
                
                $po = $createUseCase->execute(
                    $shopId,
                    $request->input('supplier_id'),
                    $request->input('currency', 'USD'),
                    $userId,
                    $expectedAt,
                    $lines
                );
            } else {
                $po = $this->createPurchaseOrderUseCase->execute(
                    $shopId,
                    $request->input('supplier_id'),
                    $request->input('currency', 'USD'),
                    $userId,
                    $expectedAt,
                    $lines
                );
            }
            
            return response()->json([
                'message' => 'Purchase order created',
                'purchase_order' => [
                    'id' => $po->getId(),
                    'status' => $po->getStatus(),
                    'total_amount' => $po->getTotal()->getAmount(),
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('PurchaseController::store - Erreur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'module' => $this->getModule(),
            ]);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Request $request, string $id): Response
    {
        $shopId = $this->getShopId($request);
        $po = $this->purchaseOrderRepository->findById($id);
        if (!$po || $po->getShopId() !== $shopId) {
            abort(404);
        }

        $isHardware = $this->getModule() === 'Hardware';
        $supplier = $isHardware 
            ? QuincaillerieSupplierModel::find($po->getSupplierId())
            : SupplierModel::find($po->getSupplierId());
        
        $lineRepo = app(\Src\Domain\Pharmacy\Repositories\PurchaseOrderLineRepositoryInterface::class);
        $poLines = $lineRepo->findByPurchaseOrder($id);
        $linesData = [];
        foreach ($poLines as $line) {
            $product = $isHardware
                ? QuincaillerieProductModel::find($line->getProductId())
                : ProductModel::find($line->getProductId());
            $linesData[] = [
                'id' => $line->getId(),
                'product_id' => $line->getProductId(),
                'product_name' => $product ? $product->name : '',
                'ordered_quantity' => $line->getOrderedQuantity()->getValue(),
                'received_quantity' => $line->getReceivedQuantity()->getValue(),
                'unit_cost' => $line->getUnitCost()->getAmount(),
                'line_total' => $line->getLineTotal()->getAmount(),
                'currency' => $line->getUnitCost()->getCurrency(),
            ];
        }

        // Fetch suppliers and products for the edit drawer
        if ($isHardware) {
            // Les fournisseurs ne sont pas filtrés par dépôt car un fournisseur peut servir plusieurs dépôts
            $suppliersQuery = QuincaillerieSupplierModel::where('shop_id', $shopId)
                ->where('status', 'active');
            
            /** @var \Illuminate\Database\Eloquent\Collection<int, QuincaillerieSupplierModel> $suppliersCollection */
            $suppliersCollection = $suppliersQuery->orderBy('name')->get();
            $suppliers = $suppliersCollection->map(function (QuincaillerieSupplierModel $s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'phone' => $s->phone,
                ];
            })->toArray();

            $productsQuery = QuincaillerieProductModel::where('shop_id', $shopId)
                ->where('is_active', true);
            
            // Appliquer le filtrage par dépôt pour Hardware
            $depotFilterService = $this->getDepotFilterService();
            if ($depotFilterService !== null) {
                $productsQuery = $depotFilterService->applyDepotFilter($productsQuery, $request, 'depot_id');
            }
            
            /** @var \Illuminate\Database\Eloquent\Collection<int, QuincaillerieProductModel> $productsCollection */
            $productsCollection = $productsQuery->orderBy('name')->get();
            $products = $productsCollection->map(function (QuincaillerieProductModel $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code ?? '',
                    'cost_amount' => isset($p->cost_amount) ? (float) $p->cost_amount : 0,
                ];
            })->toArray();
        } else {
            /** @var \Illuminate\Database\Eloquent\Collection<int, SupplierModel> $suppliersCollection */
            $suppliersCollection = SupplierModel::where('shop_id', $shopId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
            $suppliers = $suppliersCollection->map(function (SupplierModel $s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'phone' => $s->phone,
                ];
            })->toArray();

            /** @var \Illuminate\Database\Eloquent\Collection<int, ProductModel> $productsCollection */
            $productsCollection = ProductModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            $products = $productsCollection->map(function (ProductModel $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code ?? '',
                    'cost_amount' => isset($p->cost_amount) ? (float) $p->cost_amount : 0,
                ];
            })->toArray();
        }

        return Inertia::render($this->getModule() . '/Purchases/Show', [
            'purchase_order' => [
                'id' => $po->getId(),
                'status' => $po->getStatus(),
                'total_amount' => $po->getTotal()->getAmount(),
                'currency' => $po->getCurrency(),
                'supplier_id' => $po->getSupplierId(),
                'supplier_name' => $supplier ? $supplier->name : '',
                'ordered_at' => $po->getOrderedAt() ? $po->getOrderedAt()->format('Y-m-d') : null,
                'expected_at' => $po->getExpectedAt() ? $po->getExpectedAt()->format('Y-m-d') : null,
                'received_at' => $po->getReceivedAt() ? $po->getReceivedAt()->format('Y-m-d') : null,
                'created_at' => $po->getCreatedAt()->format('Y-m-d H:i'),
                'notes' => null,
            ],
            'lines' => $linesData,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $po = $this->purchaseOrderRepository->findById($id);
        if (!$po || $po->getShopId() !== $shopId) {
            return response()->json(['message' => 'Purchase order not found'], 404);
        }
        try {
            $this->confirmPurchaseOrderUseCase->execute($id);
            return response()->json(['message' => 'Purchase order confirmed']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Receive a purchase order with batch information.
     * 
     * Expected request body for full batch support:
     * {
     *     "lines": [
     *         {
     *             "line_id": "uuid",
     *             "batch_number": "LOT-001",
     *             "expiration_date": "2027-12-31",
     *             "quantity": 100  // optional, defaults to remaining quantity
     *         }
     *     ]
     * }
     */
    public function receive(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $po = $this->purchaseOrderRepository->findById($id);
        if (!$po || $po->getShopId() !== $shopId) {
            return response()->json(['message' => 'Purchase order not found'], 404);
        }

        $isHardware = $this->getModule() === 'Hardware';
        
        // Pour Hardware, créer les use cases avec l'adapter Quincaillerie
        // Note: Hardware n'utilise pas de batches, donc on crée un AddBatchUseCase qui ne fait rien
        if ($isHardware) {
            $quincaillerieProductRepository = app(QuincaillerieProductRepositoryInterface::class);
            $adapter = new QuincaillerieProductRepositoryAdapter($quincaillerieProductRepository);
            
            // Créer UpdateStockUseCase adapté pour Hardware (ne crée pas de batches)
            $updateStockUseCase = new HardwareUpdateStockUseCase(
                $adapter,
                app(\Src\Domain\Pharmacy\Repositories\StockMovementRepositoryInterface::class)
            );
            
            // Pour Hardware, on ne crée pas de batches (pas de table quincaillerie_batches)
            // On utilise un stub qui ne sauvegarde pas en base
            $addBatchUseCase = new NoOpAddBatchUseCase();
            
            // Créer ReceivePurchaseOrderUseCase avec les use cases adaptés
            // Les adapters implémentent la même interface AddBatchUseCaseInterface
            $receiveUseCase = new \Src\Application\Pharmacy\UseCases\Purchases\ReceivePurchaseOrderUseCase(
                $this->purchaseOrderRepository,
                app(\Src\Domain\Pharmacy\Repositories\PurchaseOrderLineRepositoryInterface::class),
                $updateStockUseCase,
                $addBatchUseCase
            );
        } else {
            $receiveUseCase = $this->receivePurchaseOrderUseCase;
        }
        
        try {
            // Check if batch information is provided
            $linesData = $request->input('lines', []);
            
            if (!empty($linesData)) {
                // Validate batch information
                $request->validate([
                    'lines' => 'required|array|min:1',
                    'lines.*.line_id' => 'required|string',
                    'lines.*.batch_number' => 'required|string|max:50',
                    'lines.*.expiration_date' => 'required|date|after:today',
                    'lines.*.quantity' => 'nullable|integer|min:1',
                ], [
                    'lines.*.batch_number.required' => 'Le numéro de lot est obligatoire.',
                    'lines.*.expiration_date.required' => 'La date d\'expiration est obligatoire.',
                    'lines.*.expiration_date.after' => 'La date d\'expiration doit être dans le futur.',
                ]);

                $receiveUser = $request->user();
                if ($receiveUser === null) {
                    abort(403, 'User not authenticated.');
                }
                $dto = \Src\Application\Pharmacy\DTO\ReceivePurchaseOrderDTO::fromArray(
                    $id,
                    (int) $receiveUser->id,
                    $linesData
                );

                $receiveUseCase->executeWithBatches($dto);
            } else {
                // Backward compatible: simple reception without batch info
                $receiveUser = $request->user();
                if ($receiveUser === null) {
                    abort(403, 'User not authenticated.');
                }
                $receiveUseCase->execute($id, (int) $receiveUser->id);
            }

            return response()->json(['message' => 'Réception enregistrée avec succès.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $po = $this->purchaseOrderRepository->findById($id);
        if (!$po || $po->getShopId() !== $shopId) {
            return response()->json(['message' => 'Purchase order not found'], 404);
        }
        try {
            $this->cancelPurchaseOrderUseCase->execute($id);
            return response()->json(['message' => 'Purchase order cancelled']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Export PDF d'un bon de commande spécifique.
     */
    public function exportPdf(Request $request, string $id): HttpResponse
    {
        $shopId = $this->getShopId($request);
        $po = $this->purchaseOrderRepository->findById($id);
        if (!$po || $po->getShopId() !== $shopId) {
            abort(404);
        }

        $isHardware = $this->getModule() === 'Hardware';
        $supplier = $isHardware 
            ? QuincaillerieSupplierModel::find($po->getSupplierId())
            : SupplierModel::find($po->getSupplierId());
        
        $lineRepo = app(\Src\Domain\Pharmacy\Repositories\PurchaseOrderLineRepositoryInterface::class);
        $poLines = $lineRepo->findByPurchaseOrder($id);
        $linesData = [];
        foreach ($poLines as $line) {
            $product = $isHardware
                ? QuincaillerieProductModel::find($line->getProductId())
                : ProductModel::find($line->getProductId());
            $linesData[] = [
                'product_name' => $product ? $product->name : 'Produit inconnu',
                'product_code' => $product ? ($product->code ?? '') : '',
                'ordered_quantity' => $line->getOrderedQuantity()->getValue(),
                'received_quantity' => $line->getReceivedQuantity()->getValue(),
                'unit_cost' => $line->getUnitCost()->getAmount(),
                'line_total' => $line->getLineTotal()->getAmount(),
                'currency' => $line->getUnitCost()->getCurrency(),
            ];
        }

        // Créer le header avec le shopId du bon de commande directement
        $header = $this->exportService->getExportHeader($request);
        // Enrichir avec les informations de la boutique du bon de commande (comme pour inventoryPdf)
        $header = $this->exportService->enrichHeaderWithShop($header, $po->getShopId());
        
        $statusLabels = [
            'DRAFT' => 'Brouillon',
            'CONFIRMED' => 'Confirmé',
            'PARTIALLY_RECEIVED' => 'Partiellement reçu',
            'RECEIVED' => 'Reçu',
            'CANCELLED' => 'Annulé',
        ];

        return $this->exportService->exportPdf('pharmacy.exports.purchase-order', [
            'header' => $header,
            'purchase_order' => [
                'id' => $po->getId(),
                'reference' => 'PO-' . substr($po->getId(), 0, 8),
                'status' => $po->getStatus(),
                'status_label' => $statusLabels[$po->getStatus()] ?? $po->getStatus(),
                'total_amount' => $po->getTotal()->getAmount(),
                'currency' => $po->getCurrency(),
                'supplier_name' => $supplier ? $supplier->name : '—',
                'supplier_phone' => $supplier ? ($supplier->phone ?? '') : '',
                'ordered_at' => $po->getOrderedAt() ? $po->getOrderedAt()->format('d/m/Y') : null,
                'expected_at' => $po->getExpectedAt() ? $po->getExpectedAt()->format('d/m/Y') : null,
                'received_at' => $po->getReceivedAt() ? $po->getReceivedAt()->format('d/m/Y') : null,
                'created_at' => $po->getCreatedAt()->format('d/m/Y'),
            ],
            'lines' => $linesData,
        ], 'bon_commande_' . substr($po->getId(), 0, 8));
    }

    /**
     * Export PDF thermique d'un bon de commande spécifique.
     */
    public function exportThermal(Request $request, string $id): HttpResponse
    {
        $shopId = $this->getShopId($request);
        $po = $this->purchaseOrderRepository->findById($id);
        if (!$po || $po->getShopId() !== $shopId) {
            abort(404);
        }

        $isHardware = $this->getModule() === 'Hardware';
        $supplier = $isHardware 
            ? QuincaillerieSupplierModel::find($po->getSupplierId())
            : SupplierModel::find($po->getSupplierId());
        
        $lineRepo = app(\Src\Domain\Pharmacy\Repositories\PurchaseOrderLineRepositoryInterface::class);
        $poLines = $lineRepo->findByPurchaseOrder($id);
        $linesData = [];
        foreach ($poLines as $line) {
            $product = $isHardware
                ? QuincaillerieProductModel::find($line->getProductId())
                : ProductModel::find($line->getProductId());
            $linesData[] = [
                'product_name' => $product ? $product->name : 'Produit inconnu',
                'product_code' => $product ? ($product->code ?? '') : '',
                'ordered_quantity' => $line->getOrderedQuantity()->getValue(),
                'received_quantity' => $line->getReceivedQuantity()->getValue(),
                'unit_cost' => $line->getUnitCost()->getAmount(),
                'line_total' => $line->getLineTotal()->getAmount(),
                'currency' => $line->getUnitCost()->getCurrency(),
            ];
        }

        $header = $this->exportService->getExportHeader($request);
        // Enrichir avec les informations de la boutique du bon de commande
        $header = $this->exportService->enrichHeaderWithShop($header, $po->getShopId());
        
        $statusLabels = [
            'DRAFT' => 'Brouillon',
            'CONFIRMED' => 'Confirmé',
            'PARTIALLY_RECEIVED' => 'Partiellement reçu',
            'RECEIVED' => 'Reçu',
            'CANCELLED' => 'Annulé',
        ];

        return $this->exportService->exportThermalPdf('pharmacy.exports.purchase-order-thermal', [
            'header' => $header,
            'purchase_order' => [
                'id' => $po->getId(),
                'reference' => 'PO-' . substr($po->getId(), 0, 8),
                'status' => $po->getStatus(),
                'status_label' => $statusLabels[$po->getStatus()] ?? $po->getStatus(),
                'total_amount' => $po->getTotal()->getAmount(),
                'currency' => $po->getCurrency(),
                'supplier_name' => $supplier ? $supplier->name : '—',
                'supplier_phone' => $supplier ? ($supplier->phone ?? '') : '',
                'ordered_at' => $po->getOrderedAt() ? $po->getOrderedAt()->format('d/m/Y H:i') : null,
                'expected_at' => $po->getExpectedAt() ? $po->getExpectedAt()->format('d/m/Y') : null,
                'received_at' => $po->getReceivedAt() ? $po->getReceivedAt()->format('d/m/Y H:i') : null,
                'created_at' => $po->getCreatedAt()->format('d/m/Y H:i'),
            ],
            'lines' => $linesData,
        ], 'bon_commande_' . substr($po->getId(), 0, 8));
    }
}
