<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;
use Src\Application\Pharmacy\UseCases\Sales\CreateDraftSaleUseCase;
use Src\Application\Pharmacy\UseCases\Sales\UpdateSaleLinesUseCase;
use Src\Application\Pharmacy\UseCases\Sales\FinalizeSaleUseCase;
use Src\Application\Pharmacy\UseCases\Sales\CancelSaleUseCase;
use Src\Application\Pharmacy\UseCases\Sales\AttachCustomerToSaleUseCase;
use Src\Domain\Pharmacy\Repositories\SaleRepositoryInterface;
use Src\Domain\Pharmacy\Repositories\SaleLineRepositoryInterface;
use Src\Application\Pharmacy\DTO\SaleLineDTO;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Models\SaleModel;

class SaleController
{
    public function __construct(
        private CreateDraftSaleUseCase $createDraftSaleUseCase,
        private UpdateSaleLinesUseCase $updateSaleLinesUseCase,
        private FinalizeSaleUseCase $finalizeSaleUseCase,
        private CancelSaleUseCase $cancelSaleUseCase,
        private AttachCustomerToSaleUseCase $attachCustomerToSaleUseCase,
        private SaleRepositoryInterface $saleRepository,
        private SaleLineRepositoryInterface $saleLineRepository
    ) {}

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
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
        $from = $request->filled('from') ? new \DateTimeImmutable($request->input('from')) : null;
        $to = $request->filled('to') ? new \DateTimeImmutable($request->input('to')) : null;
        $status = $request->input('status');

        $sales = $this->saleRepository->findByShop($shopId, $from, $to);
        if ($status && in_array($status, ['DRAFT', 'COMPLETED', 'CANCELLED'], true)) {
            $sales = array_values(array_filter($sales, fn ($s) => $s->getStatus() === $status));
        }

        $salesData = array_map(function ($sale) {
            return [
                'id' => $sale->getId(),
                'status' => $sale->getStatus(),
                'total_amount' => $sale->getTotal()->getAmount(),
                'paid_amount' => $sale->getPaidAmount()->getAmount(),
                'balance_amount' => $sale->getBalance()->getAmount(),
                'currency' => $sale->getCurrency(),
                'customer_id' => $sale->getCustomerId(),
                'created_at' => $sale->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }, $sales);

        return Inertia::render('Pharmacy/Sales/Index', [
            'sales' => $salesData,
            'filters' => $request->only(['from', 'to', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        
        // Products with images
        $imageService = app(\Src\Infrastructure\Pharmacy\Services\ProductImageService::class);
        $products = ProductModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code ?? '',
                'price_amount' => (float) ($p->price_amount ?? 0),
                'price_currency' => $p->price_currency ?? 'USD',
                'stock' => (int) ($p->stock ?? 0),
                'category_id' => $p->category_id,
                'image_url' => $imageService->getUrlFromPath($p->image_path, $p->image_type ?? 'upload'),
            ])->toArray();

        // Categories
        $categories = \Src\Infrastructure\Pharmacy\Models\CategoryModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->toArray();

        $tenantId = $request->user()->tenant_id ?? $shopId;
        $customers = \App\Models\Customer::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get()
            ->map(fn ($c) => [
                'id' => (string) $c->id,
                'full_name' => $c->full_name,
                'phone' => $c->phone,
                'email' => $c->email,
            ])->toArray();

        return Inertia::render('Pharmacy/Sales/Create', [
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'nullable|string',
            'currency' => 'required|string|size:3',
            'lines' => 'required|array',
            'lines.*.product_id' => 'required|string',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $shopId = $this->getShopId($request);
        $userId = (int) $request->user()->id;
        $customerId = $request->input('customer_id') ?: null;
        $currency = $request->input('currency', 'USD');
        $linesInput = $request->input('lines', []);

        $sale = $this->createDraftSaleUseCase->execute($shopId, $customerId, $currency, $userId);

        $lines = [];
        foreach ($linesInput as $row) {
            $lines[] = new SaleLineDTO(
                $row['product_id'],
                (int) $row['quantity'],
                (float) $row['unit_price'],
                isset($row['discount_percent']) ? (float) $row['discount_percent'] : null
            );
        }

        if (count($lines) > 0) {
            $this->updateSaleLinesUseCase->execute($sale->getId(), $lines);
        }

        if ($customerId) {
            $this->attachCustomerToSaleUseCase->execute($sale->getId(), $customerId);
            $sale = $this->saleRepository->findById($sale->getId());
        }

        return response()->json([
            'message' => 'Draft sale created',
            'sale' => [
                'id' => $sale->getId(),
                'status' => $sale->getStatus(),
                'total_amount' => $sale->getTotal()->getAmount(),
                'currency' => $sale->getCurrency(),
            ],
        ], 201);
    }

    public function show(Request $request, string $id): Response|JsonResponse
    {
        $shopId = $this->getShopId($request);
        $sale = $this->saleRepository->findById($id);
        if (!$sale || $sale->getShopId() !== $shopId) {
            abort(404);
        }

        $lines = $this->saleLineRepository->findBySale($id);
        $linesData = [];
        foreach ($lines as $line) {
            $product = ProductModel::find($line->getProductId());
            $linesData[] = [
                'id' => $line->getId(),
                'product_id' => $line->getProductId(),
                'product_name' => $product ? $product->name : '',
                'quantity' => $line->getQuantity()->getValue(),
                'unit_price' => $line->getUnitPrice()->getAmount(),
                'line_total' => $line->getLineTotal()->getAmount(),
                'currency' => $line->getUnitPrice()->getCurrency(),
            ];
        }

        $customer = null;
        if ($sale->getCustomerId()) {
            $c = \App\Models\Customer::find($sale->getCustomerId());
            if ($c) {
                $customer = ['id' => (string) $c->id, 'full_name' => $c->full_name, 'phone' => $c->phone];
            }
        }

        return Inertia::render('Pharmacy/Sales/Show', [
            'sale' => [
                'id' => $sale->getId(),
                'status' => $sale->getStatus(),
                'total_amount' => $sale->getTotal()->getAmount(),
                'paid_amount' => $sale->getPaidAmount()->getAmount(),
                'balance_amount' => $sale->getBalance()->getAmount(),
                'currency' => $sale->getCurrency(),
                'created_at' => $sale->getCreatedAt()->format('Y-m-d H:i'),
                'completed_at' => $sale->getCompletedAt() ? $sale->getCompletedAt()->format('Y-m-d H:i') : null,
            ],
            'lines' => $linesData,
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'customer_id' => 'nullable|string',
            'lines' => 'required|array',
            'lines.*.product_id' => 'required|string',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $shopId = $this->getShopId($request);
        $sale = $this->saleRepository->findById($id);
        if (!$sale || $sale->getShopId() !== $shopId || $sale->getStatus() !== \Src\Domain\Pharmacy\Entities\Sale::STATUS_DRAFT) {
            return response()->json(['message' => 'Sale not found or not editable'], 404);
        }

        $lines = [];
        foreach ($request->input('lines', []) as $row) {
            $lines[] = new SaleLineDTO(
                $row['product_id'],
                (int) $row['quantity'],
                (float) $row['unit_price'],
                isset($row['discount_percent']) ? (float) $row['discount_percent'] : null
            );
        }
        $this->updateSaleLinesUseCase->execute($id, $lines);

        if ($request->has('customer_id')) {
            $cid = $request->input('customer_id');
            if ($cid) {
                $this->attachCustomerToSaleUseCase->execute($id, $cid);
            }
        }

        $sale = $this->saleRepository->findById($id);
        return response()->json([
            'message' => 'Sale updated',
            'sale' => [
                'id' => $sale->getId(),
                'total_amount' => $sale->getTotal()->getAmount(),
            ],
        ]);
    }

    public function finalize(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
        ]);

        $shopId = $this->getShopId($request);
        $sale = $this->saleRepository->findById($id);
        if (!$sale || $sale->getShopId() !== $shopId) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        try {
            $this->finalizeSaleUseCase->execute($id, (float) $request->input('paid_amount'), (int) $request->user()->id);
            return response()->json(['message' => 'Sale finalized successfully']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $sale = $this->saleRepository->findById($id);
        if (!$sale || $sale->getShopId() !== $shopId) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        try {
            $this->cancelSaleUseCase->execute($id);
            return response()->json(['message' => 'Sale cancelled']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
