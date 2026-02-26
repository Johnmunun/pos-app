<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Models\CategoryModel;
use Src\Infrastructure\Pharmacy\Models\BatchModel;
use Src\Infrastructure\Pharmacy\Models\StockMovementModel;

class StockController
{
    private function getModule(): string
    {
        $prefix = request()->route()?->getPrefix();
        return $prefix === 'hardware' ? 'Hardware' : 'Pharmacy';
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
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);
        $userModel = UserModel::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }

        // Base query produits
        $query = ProductModel::with('category')->orderBy('name');
        if (!($isRoot && !$shopId)) {
            $query->where('shop_id', $shopId);
        }

        // Filtres
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('stock_status')) {
            $status = $request->input('stock_status');
            if ($status === 'low') {
                $query->where('stock', '>', 0)
                    ->whereColumn('stock', '<=', 'minimum_stock');
            } elseif ($status === 'out') {
                $query->where(function ($q) {
                    $q->whereNull('stock')->orWhere('stock', '<=', 0);
                });
            }
        }

        // Pagination simple
        $perPage = (int) $request->input('per_page', 15);
        $paginator = $query->paginate($perPage)->appends($request->query());

        $products = $paginator->getCollection()->map(function ($model) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'product_code' => $model->code ?? $model->product_code ?? '',
                'current_stock' => (int) ($model->stock ?? 0),
                'minimum_stock' => (int) ($model->minimum_stock ?? 0),
                'category' => $model->category ? [
                    'id' => $model->category->id,
                    'name' => $model->category->name,
                ] : null,
            ];
        })->toArray();

        $pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        // Low stock (alert) – non filtré par recherche
        $lowStockQuery = ProductModel::with('category')->orderBy('name');
        if (!($isRoot && !$shopId)) {
            $lowStockQuery->where('shop_id', $shopId);
        }
        $lowStockModels = $lowStockQuery
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->limit(20)
            ->get();

        $lowStock = $lowStockModels->map(function ($model) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'product_code' => $model->code ?? $model->product_code ?? '',
                'current_stock' => (int) ($model->stock ?? 0),
                'minimum_stock' => (int) ($model->minimum_stock ?? 0),
            ];
        })->toArray();

        // Batches qui expirent bientôt
        $batchQuery = BatchModel::with('product')->orderBy('expiry_date');
        if (!($isRoot && !$shopId)) {
            $batchQuery->byShop((string) $shopId);
        }
        $expiringSoonModels = $batchQuery->expiringSoon(30)->limit(20)->get();

        $expiringSoon = $expiringSoonModels->map(function ($batch) {
            return [
                'id' => $batch->id,
                'product_name' => $batch->product ? $batch->product->name : '',
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : null,
                'days_until_expiry' => $batch->days_until_expiry ?? null,
            ];
        })->toArray();

        // Catégories pour filtres
        $categoryModels = CategoryModel::query()
            ->when(!($isRoot && !$shopId), function ($q) use ($shopId) {
                $q->where('shop_id', $shopId);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = $categoryModels->map(function ($model) {
            return [
                'id' => $model->id,
                'name' => $model->name,
            ];
        })->toArray();

        return Inertia::render($this->getModule() . '/Stock/Index', [
            'products' => $products,
            'lowStock' => $lowStock,
            'expiringSoon' => $expiringSoon,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'stock_status', 'per_page']),
            'pagination' => $pagination,
            'routePrefix' => $this->getModule() === 'Hardware' ? 'hardware' : 'pharmacy',
        ]);
    }

    public function movements(Request $request, string $productId)
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);
        $userModel = UserModel::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        $query = StockMovementModel::where('product_id', $productId)->orderBy('created_at', 'desc');
        if (!($isRoot && !$shopId)) {
            $query->where('shop_id', $shopId);
        }

        $movements = $query->limit(50)->get()->map(function (StockMovementModel $model) {
            return [
                'id' => $model->id,
                'type' => $model->type,
                'quantity' => (int) $model->quantity,
                'reference' => $model->reference,
                'created_at' => $model->created_at ? \Carbon\Carbon::parse($model->created_at)->format('Y-m-d H:i') : null,
                'created_by' => $model->created_by,
            ];
        })->toArray();

        return response()->json(['movements' => $movements]);
    }

    /**
     * Liste globale des mouvements de stock avec filtres (dates, type, référence).
     */
    public function movementsIndex(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);
        $userModel = UserModel::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        $query = StockMovementModel::with('product:id,name,code')->orderBy('created_at', 'desc');
        if (!($isRoot && !$shopId)) {
            $query->where('shop_id', $shopId);
        }
        if ($request->filled('type') && in_array($request->input('type'), ['IN', 'OUT', 'ADJUSTMENT'], true)) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->input('reference') . '%');
        }

        $perPage = (int) $request->input('per_page', 20);
        $paginator = $query->paginate($perPage)->appends($request->query());

        $movements = $paginator->getCollection()->map(function (StockMovementModel $model) {
            return [
                'id' => $model->id,
                'type' => $model->type,
                'quantity' => (int) $model->quantity,
                'reference' => $model->reference,
                'product_id' => $model->product_id,
                'product_name' => $model->product ? $model->product->name : null,
                'product_code' => $model->product ? ($model->product->code ?? '') : null,
                'created_at' => $model->created_at ? \Carbon\Carbon::parse($model->created_at)->format('Y-m-d H:i') : null,
                'created_by' => $model->created_by,
            ];
        })->toArray();

        return Inertia::render($this->getModule() . '/Stock/Movements', [
            'movements' => $movements,
            'filters' => $request->only(['type', 'from', 'to', 'reference', 'per_page']),
            'routePrefix' => $this->getModule() === 'Hardware' ? 'hardware' : 'pharmacy',
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}

