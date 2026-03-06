<?php

declare(strict_types=1);

namespace Src\Infrastructure\Quincaillerie\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User as UserModel;
use Src\Application\Quincaillerie\Services\InventoryService;
use Src\Infrastructure\Quincaillerie\Models\InventoryModel;
use Src\Infrastructure\Quincaillerie\Models\InventoryItemModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel;
use Src\Infrastructure\Quincaillerie\Models\CategoryModel;
use Src\Application\Quincaillerie\Services\DepotFilterService;

/**
 * Controller : InventoryController - Module Hardware
 *
 * Gère les inventaires physiques du stock.
 * SÉCURITÉ : Isolation stricte par shop_id et depot_id (multi-tenant)
 */
class InventoryController
{
    public function __construct(
        private InventoryService $inventoryService,
        private DepotFilterService $depotFilterService
    ) {}

    /**
     * Liste des inventaires
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);
        $isRoot = $this->isRoot($user);

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found.');
        }

        // Filtres
        $filters = $request->only(['status', 'from', 'to', 'reference', 'depot_id']);

        // Query avec relations
        $query = InventoryModel::with(['creator:id,name,email', 'validator:id,name,email', 'depot:id,name'])
            ->withCount('items')
            ->orderBy('created_at', 'desc');

        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        // Appliquer le filtrage par dépôt selon les permissions
        $query = $this->depotFilterService->applyDepotFilter($query, $request, 'depot_id');

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

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $inventories = $paginator->getCollection()->map(function (InventoryModel $inv) {
            return [
                'id' => $inv->id,
                'reference' => $inv->reference,
                'status' => $inv->status,
                'depot_id' => $inv->depot_id,
                'depot_name' => $inv->depot?->name,
                'items_count' => $inv->items_count,
                'started_at' => $inv->started_at?->format('d/m/Y H:i'),
                'validated_at' => $inv->validated_at?->format('d/m/Y H:i'),
                'created_at' => $inv->created_at->format('d/m/Y H:i'),
                'creator' => $inv->creator ? [
                    'id' => $inv->creator->id,
                    'name' => $inv->creator->name,
                ] : null,
                'validator' => $inv->validator ? [
                    'id' => $inv->validator->id,
                    'name' => $inv->validator->name,
                ] : null,
            ];
        })->toArray();

        return Inertia::render('Hardware/Inventories/Index', [
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

    /**
     * Crée un nouvel inventaire
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);

        if (!$shopId) {
            return redirect()->back()->withErrors(['message' => 'Shop ID not found.']);
        }

        // Obtenir le depot_id effectif selon les permissions
        $depotId = $this->depotFilterService->getEffectiveDepotId($request);

        try {
            $userId = method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : (int) ($user->id ?? 0);
            $inventory = $this->inventoryService->createInventory($shopId, $depotId, $userId);

            return redirect()->route('hardware.inventories.show', $inventory->getId())
                ->with('success', 'Inventaire créé avec succès. Vous pouvez maintenant le démarrer.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Affiche un inventaire avec ses items
     */
    public function show(Request $request, string $id): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);
        $isRoot = $this->isRoot($user);

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found.');
        }

        // Récupérer l'inventaire
        $query = InventoryModel::with(['creator:id,name,email', 'validator:id,name,email', 'depot:id,name']);
        
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        // Appliquer le filtrage par dépôt selon les permissions
        $query = $this->depotFilterService->applyDepotFilter($query, $request, 'depot_id');

        $inventoryModel = $query->where('id', $id)->first();

        if (!$inventoryModel) {
            abort(404, 'Inventaire non trouvé.');
        }

        // Récupérer les items avec les produits
        $items = InventoryItemModel::query()
            ->with(['product:id,name,code,stock,category_id', 'product.category:id,name'])
            ->where('inventory_id', $id)
            ->get()
            ->map(function ($item) {
                $productName = 'Produit inconnu';
                $productCode = '';
                $categoryName = '';
                
                if ($item->product !== null) {
                    $productName = $item->product->name ?? 'Produit inconnu';
                    $productCode = $item->product->code ?? '';
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
            })
            ->toArray();

        /** @var InventoryModel $inventoryModel */
        $inventory = [
            'id' => $inventoryModel->id,
            'reference' => $inventoryModel->reference,
            'status' => $inventoryModel->status,
            'depot_id' => $inventoryModel->depot_id,
            'depot_name' => $inventoryModel->depot?->name,
            'started_at' => $inventoryModel->started_at?->format('d/m/Y H:i'),
            'validated_at' => $inventoryModel->validated_at?->format('d/m/Y H:i'),
            'created_at' => $inventoryModel->created_at->format('d/m/Y H:i'),
            'creator' => $inventoryModel->creator ? [
                'id' => $inventoryModel->creator->id,
                'name' => $inventoryModel->creator->name,
            ] : null,
            'validator' => $inventoryModel->validator ? [
                'id' => $inventoryModel->validator->id,
                'name' => $inventoryModel->validator->name,
            ] : null,
        ];

        // Calculer les statistiques
        $stats = [
            'total_items' => count($items),
            'counted_items' => collect($items)->filter(fn($i) => $i['counted_quantity'] !== null)->count(),
            'items_with_difference' => collect($items)->filter(fn($i) => abs($i['difference']) > 0.01)->count(),
            'total_positive' => collect($items)->filter(fn($i) => $i['difference'] > 0.01)->sum('difference'),
            'total_negative' => abs(collect($items)->filter(fn($i) => $i['difference'] < -0.01)->sum('difference')),
        ];

        // Produits disponibles pour ajouter (si inventaire en brouillon)
        $availableProducts = [];
        if ($inventoryModel->status === 'draft') {
            $existingProductIds = collect($items)->pluck('product_id')->toArray();
            
            $productsQuery = ProductModel::with('category:id,name')
                ->where('is_active', true)
                ->whereNotIn('id', $existingProductIds);

            if (!$isRoot || $shopId) {
                $productsQuery->where('shop_id', $shopId);
            }

            // Filtrer par dépôt si l'inventaire est lié à un dépôt
            if ($inventoryModel->depot_id !== null) {
                $productsQuery->where('depot_id', $inventoryModel->depot_id);
            } else {
                // Appliquer le filtrage par dépôt selon les permissions
                $productsQuery = $this->depotFilterService->applyDepotFilter($productsQuery, $request, 'depot_id');
            }

            $availableProducts = $productsQuery->orderBy('name')->get()->map(function (ProductModel $p) {
                $categoryName = '';
                if ($p->category !== null) {
                    $categoryName = $p->category->name ?? '';
                }
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code ?? '',
                    'category' => $categoryName,
                    'stock' => (float) ($p->stock ?? 0),
                ];
            })->toArray();
        }

        // Catégories pour filtres
        $categoriesQuery = CategoryModel::query()->where('is_active', true)->orderBy('name');
        if (!$isRoot || $shopId) {
            $categoriesQuery->where('shop_id', $shopId);
        }
        $categories = $categoriesQuery->get()->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();

        return Inertia::render('Hardware/Inventories/Show', [
            'inventory' => $inventory,
            'items' => $items,
            'stats' => $stats,
            'availableProducts' => $availableProducts,
            'categories' => $categories,
            'permissions' => $this->getPermissions($user),
        ]);
    }

    /**
     * Démarre un inventaire (crée le snapshot)
     */
    public function start(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);

        if (!$shopId) {
            return redirect()->back()->withErrors(['message' => 'Shop ID not found.']);
        }

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

    /**
     * Met à jour les quantités comptées
     */
    public function updateCounts(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);

        if (!$shopId) {
            return response()->json(['message' => 'Shop ID not found.'], 403);
        }

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

    /**
     * Met à jour une seule quantité comptée
     */
    public function updateSingleCount(Request $request, string $id, string $productId): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);

        if (!$shopId) {
            return response()->json(['message' => 'Shop ID not found.'], 403);
        }

        $validated = $request->validate([
            'counted_quantity' => 'required|numeric|min:0',
        ]);

        try {
            $item = $this->inventoryService->updateItemCount(
                $id,
                $shopId,
                $productId,
                (float) $validated['counted_quantity']
            );

            return response()->json([
                'message' => 'Quantité mise à jour.',
                'item' => [
                    'id' => $item->getId(),
                    'counted_quantity' => $item->getCountedQuantity(),
                    'difference' => $item->getDifference(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Valide un inventaire et applique les ajustements
     */
    public function validate(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);

        if (!$shopId) {
            return redirect()->back()->withErrors(['message' => 'Shop ID not found.']);
        }

        try {
            $userId = method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : (int) ($user->id ?? 0);
            $this->inventoryService->validateInventory($id, $shopId, $userId);

            return redirect()->back()->with('success', 'Inventaire validé avec succès. Les ajustements de stock ont été appliqués.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Annule un inventaire
     */
    public function cancel(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $this->getShopId($request);

        if (!$shopId) {
            return redirect()->back()->withErrors(['message' => 'Shop ID not found.']);
        }

        try {
            $this->inventoryService->cancelInventory($id, $shopId);

            return redirect()->back()->with('success', 'Inventaire annulé.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Shop ID selon le dépôt sélectionné en session (ou fallback user).
     */
    private function getShopId(Request $request): ?string
    {
        $user = $request->user();
        if ($user === null) {
            return null;
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
        return $shopId !== null ? (string) $shopId : null;
    }

    /**
     * Vérifie si l'utilisateur est ROOT
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user
     */
    private function isRoot($user): bool
    {
        if ($user === null) {
            return false;
        }
        $userId = method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : (int) ($user->id ?? 0);
        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($userId);
        return $userModel !== null && $userModel->isRoot();
    }

    /**
     * Récupère les permissions de l'utilisateur
     * 
     * @param mixed $user
     * @return array<string, bool>
     */
    private function getPermissions($user): array
    {
        if ($user === null) {
            return ['view' => false, 'create' => false, 'edit' => false, 'validate' => false, 'cancel' => false];
        }
        $userId = method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : (int) ($user->id ?? 0);
        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($userId);
        $isRoot = $userModel !== null && $userModel->isRoot();
        /** @var array<string> $codes */
        $codes = $userModel !== null ? $userModel->permissionCodes() : [];

        return [
            'view' => $isRoot || in_array('inventory.view', $codes, true),
            'create' => $isRoot || in_array('inventory.create', $codes, true),
            'edit' => $isRoot || in_array('inventory.edit', $codes, true),
            'validate' => $isRoot || in_array('inventory.validate', $codes, true),
            'cancel' => $isRoot || in_array('inventory.cancel', $codes, true),
        ];
    }
}
