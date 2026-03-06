<?php

declare(strict_types=1);

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;
use Src\Application\GlobalCommerce\Services\GcInventoryService;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcInventoryModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcInventoryItemModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;

/**
 * Contrôleur inventaires physiques - Module Global Commerce
 */
class GcInventoryController
{
    public function __construct(
        private GcInventoryService $inventoryService
    ) {}

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::where('depot_id', (int) $depotId)
                ->where('tenant_id', $user->tenant_id)
                ->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }

        if ($user->shop_id !== null && $user->shop_id !== '') {
            return (string) $user->shop_id;
        }

        if ($user->tenant_id) {
            return (string) $user->tenant_id;
        }

        abort(403, 'Shop ID not found.');
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $shopId = $this->getShopId($request);

        $filters = $request->only(['status', 'from', 'to', 'reference']);

        $query = GcInventoryModel::with(['creator:id,name,email', 'validator:id,name,email'])
            ->withCount('items')
            ->where('shop_id', (int) $shopId)
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if (!empty($filters['reference'])) {
            $query->where('reference', 'like', '%' . $filters['reference'] . '%');
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginator = $query->paginate($perPage)->appends($request->query());

        $inventories = $paginator->getCollection()->map(function (GcInventoryModel $inv) {
            return [
                'id' => $inv->id,
                'reference' => $inv->reference,
                'status' => $inv->status,
                'items_count' => $inv->items_count,
                'started_at' => $inv->started_at?->format('d/m/Y H:i'),
                'validated_at' => $inv->validated_at?->format('d/m/Y H:i'),
                'created_at' => $inv->created_at->format('d/m/Y H:i'),
                'creator' => $inv->creator ? ['id' => $inv->creator->id, 'name' => $inv->creator->name] : null,
                'validator' => $inv->validator ? ['id' => $inv->validator->id, 'name' => $inv->validator->name] : null,
            ];
        })->toArray();

        return Inertia::render('Commerce/Inventories/Index', [
            'inventories' => $inventories,
            'filters' => $filters,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'permissions' => $this->getPermissions($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $shopId = $this->getShopId($request);

        try {
            $userId = method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : (int) ($user->id ?? 0);
            $inventory = $this->inventoryService->createInventory($shopId, $userId);

            return redirect()->route('commerce.inventories.show', $inventory->id)
                ->with('success', 'Inventaire créé avec succès. Vous pouvez maintenant le démarrer.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function show(Request $request, string $id): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $shopId = $this->getShopId($request);

        $inventoryModel = GcInventoryModel::with(['creator:id,name,email', 'validator:id,name,email'])
            ->where('id', $id)
            ->where('shop_id', (int) $shopId)
            ->first();

        if (!$inventoryModel) {
            abort(404, 'Inventaire non trouvé.');
        }

        $items = GcInventoryItemModel::query()
            ->with(['product:id,sku,barcode,name,category_id', 'product.category:id,name'])
            ->where('inventory_id', $id)
            ->get()
            ->map(function ($item) {
                $productName = 'Produit inconnu';
                $productCode = '';
                $categoryName = '';
                if ($item->product !== null) {
                    $productName = $item->product->name ?? 'Produit inconnu';
                    $productCode = $item->product->sku ?? $item->product->barcode ?? '';
                    if ($item->product->category !== null) {
                        $categoryName = $item->product->category->name ?? '';
                    }
                }
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $productName,
                    'product_code' => $productCode,
                    'category_name' => $categoryName,
                    'system_quantity' => (float) $item->system_quantity,
                    'counted_quantity' => $item->counted_quantity !== null ? (float) $item->counted_quantity : null,
                    'difference' => (float) $item->difference,
                ];
            })->toArray();

        $stats = [
            'total_items' => count($items),
            'counted_items' => collect($items)->filter(fn ($i) => $i['counted_quantity'] !== null)->count(),
            'items_with_difference' => collect($items)->filter(fn ($i) => abs($i['difference']) > 0.01)->count(),
            'total_positive' => collect($items)->sum(fn ($i) => $i['difference'] > 0 ? $i['difference'] : 0),
            'total_negative' => collect($items)->sum(fn ($i) => $i['difference'] < 0 ? abs($i['difference']) : 0),
        ];

        $categories = CategoryModel::where('shop_id', (int) $shopId)->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();

        $inventory = [
            'id' => $inventoryModel->id,
            'reference' => $inventoryModel->reference,
            'status' => $inventoryModel->status,
            'started_at' => $inventoryModel->started_at?->format('d/m/Y H:i'),
            'validated_at' => $inventoryModel->validated_at?->format('d/m/Y H:i'),
            'created_at' => $inventoryModel->created_at->format('d/m/Y H:i'),
            'creator' => $inventoryModel->creator ? ['id' => $inventoryModel->creator->id, 'name' => $inventoryModel->creator->name] : null,
            'validator' => $inventoryModel->validator ? ['id' => $inventoryModel->validator->id, 'name' => $inventoryModel->validator->name] : null,
        ];

        return Inertia::render('Commerce/Inventories/Show', [
            'inventory' => $inventory,
            'items' => $items,
            'stats' => $stats,
            'categories' => $categories,
            'permissions' => $this->getPermissions($user),
        ]);
    }

    public function start(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'string',
        ]);

        try {
            $productIds = $validated['product_ids'] ?? null;
            $this->inventoryService->startInventory($id, $shopId, $productIds);

            return redirect()->back()->with('success', 'Inventaire démarré avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function updateCounts(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'counts' => 'required|array',
            'counts.*.product_id' => 'required|string',
            'counts.*.counted_quantity' => 'required|numeric|min:0',
        ]);

        try {
            $counts = [];
            foreach ($validated['counts'] as $item) {
                $counts[$item['product_id']] = (float) $item['counted_quantity'];
            }
            $this->inventoryService->updateItemCounts($id, $shopId, $counts);

            return response()->json(['message' => 'Quantités mises à jour avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function validate(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $shopId = $this->getShopId($request);

        try {
            $userId = method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : (int) ($user->id ?? 0);
            $this->inventoryService->validateInventory($id, $shopId, $userId);

            return redirect()->back()->with('success', 'Inventaire validé avec succès. Les ajustements de stock ont été appliqués.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $shopId = $this->getShopId($request);

        try {
            $this->inventoryService->cancelInventory($id, $shopId);

            return redirect()->back()->with('success', 'Inventaire annulé.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    private function getPermissions($user): array
    {
        if ($user === null) {
            return ['view' => false, 'create' => false, 'edit' => false, 'validate' => false, 'cancel' => false];
        }
        $userId = method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : (int) ($user->id ?? 0);
        $userModel = UserModel::query()->find($userId);
        $isRoot = $userModel !== null && $userModel->isRoot();
        $codes = $userModel !== null ? $userModel->permissionCodes() : [];

        return [
            'view' => $isRoot || in_array('module.commerce', $codes, true),
            'create' => $isRoot || in_array('module.commerce', $codes, true),
            'edit' => $isRoot || in_array('module.commerce', $codes, true),
            'validate' => $isRoot || in_array('module.commerce', $codes, true),
            'cancel' => $isRoot || in_array('module.commerce', $codes, true),
        ];
    }
}
