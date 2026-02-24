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

class PurchaseController
{
    public function __construct(
        private CreatePurchaseOrderUseCase $createPurchaseOrderUseCase,
        private ConfirmPurchaseOrderUseCase $confirmPurchaseOrderUseCase,
        private ReceivePurchaseOrderUseCase $receivePurchaseOrderUseCase,
        private CancelPurchaseOrderUseCase $cancelPurchaseOrderUseCase,
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {}

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

        $pos = $this->purchaseOrderRepository->findByShop($shopId, $status, $from, $to);
        $list = [];
        foreach ($pos as $po) {
            $supplier = SupplierModel::find($po->getSupplierId());
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
        $suppliers = SupplierModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone,
            ])->toArray();

        $products = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code ?? '',
                'cost_amount' => isset($p->cost_amount) ? (float) $p->cost_amount : 0,
            ])->toArray();

        return Inertia::render('Pharmacy/Purchases/Index', [
            'purchase_orders' => $list,
            'filters' => $request->only(['from', 'to', 'status']),
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
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

        return Inertia::render('Pharmacy/Purchases/Create', [
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
            $po = $this->createPurchaseOrderUseCase->execute(
                $shopId,
                $request->input('supplier_id'),
                $request->input('currency', 'USD'),
                $userId,
                $expectedAt,
                $lines
            );
            return response()->json([
                'message' => 'Purchase order created',
                'purchase_order' => [
                    'id' => $po->getId(),
                    'status' => $po->getStatus(),
                    'total_amount' => $po->getTotal()->getAmount(),
                ],
            ], 201);
        } catch (\Throwable $e) {
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

        $supplier = SupplierModel::find($po->getSupplierId());
        $lineRepo = app(\Src\Domain\Pharmacy\Repositories\PurchaseOrderLineRepositoryInterface::class);
        $poLines = $lineRepo->findByPurchaseOrder($id);
        $linesData = [];
        foreach ($poLines as $line) {
            $product = ProductModel::find($line->getProductId());
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
        $suppliers = SupplierModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone,
            ])->toArray();

        $products = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code ?? '',
                'cost_amount' => isset($p->cost_amount) ? (float) $p->cost_amount : 0,
            ])->toArray();

        return Inertia::render('Pharmacy/Purchases/Show', [
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

                $this->receivePurchaseOrderUseCase->executeWithBatches($dto);
            } else {
                // Backward compatible: simple reception without batch info
                $receiveUser = $request->user();
                if ($receiveUser === null) {
                    abort(403, 'User not authenticated.');
                }
                $this->receivePurchaseOrderUseCase->execute($id, (int) $receiveUser->id);
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
}
