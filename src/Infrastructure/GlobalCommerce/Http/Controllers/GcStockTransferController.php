<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\GlobalCommerce\Inventory\Services\GcStockTransferService;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

/**
 * Contrôleur des transferts de stock inter-magasins - Module GlobalCommerce.
 */
class GcStockTransferController
{
    public function __construct(
        private readonly GcStockTransferService $transferService,
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;
        if (!$depotId && $request->hasSession()) {
            $depotId = $request->session()->get('current_depot_id');
        }
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = Shop::where('depot_id', (int) $depotId)
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

    private function getTenantId(Request $request): string
    {
        $user = $request->user();
        if ($user === null || !$user->tenant_id) {
            abort(403, 'Tenant not found.');
        }
        return (string) $user->tenant_id;
    }

    private function isRoot(Request $request): bool
    {
        $user = $request->user();
        if ($user === null) {
            return false;
        }
        $userModel = UserModel::query()->find($user->id);
        return $userModel !== null && $userModel->isRoot();
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $tenantId = $this->getTenantId($request);
        $isRoot = $this->isRoot($request);

        $filters = array_filter([
            'status' => $request->input('status'),
            'from_shop_id' => $request->input('from_shop_id'),
            'to_shop_id' => $request->input('to_shop_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'reference' => $request->input('reference'),
        ], fn ($v) => $v !== null && $v !== '');

        $transfers = $isRoot
            ? $this->transferService->getAllTransfers($filters)
            : $this->transferService->getTransfers($tenantId, $filters);

        $transfersData = [];
        foreach ($transfers as $transfer) {
            $fromShop = Shop::query()->find($transfer->getFromShopId());
            $toShop = Shop::query()->find($transfer->getToShopId());
            $creator = UserModel::query()->find($transfer->getCreatedBy());
            $validator = $transfer->getValidatedBy() !== null
                ? UserModel::query()->find($transfer->getValidatedBy())
                : null;

            $transfersData[] = [
                'id' => $transfer->getId(),
                'reference' => $transfer->getReference(),
                'from_shop_id' => $transfer->getFromShopId(),
                'from_shop_name' => $fromShop?->name ?? 'Magasin inconnu',
                'to_shop_id' => $transfer->getToShopId(),
                'to_shop_name' => $toShop?->name ?? 'Magasin inconnu',
                'status' => $transfer->getStatus(),
                'total_items' => $transfer->getTotalItems(),
                'total_quantity' => $transfer->getTotalQuantity(),
                'created_by_name' => $creator?->name ?? 'Utilisateur inconnu',
                'validated_by_name' => $validator?->name,
                'created_at' => $transfer->getCreatedAt()->format('Y-m-d H:i:s'),
                'created_at_formatted' => $transfer->getCreatedAt()->format('d/m/Y H:i'),
                'validated_at' => $transfer->getValidatedAt()?->format('Y-m-d H:i:s'),
                'validated_at_formatted' => $transfer->getValidatedAt()?->format('d/m/Y H:i'),
                'notes' => $transfer->getNotes(),
            ];
        }

        $shops = Shop::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code ?? ''])
            ->toArray();

        $stats = ['total' => count($transfers), 'draft' => 0, 'validated' => 0, 'cancelled' => 0];
        foreach ($transfers as $t) {
            if (isset($stats[$t->getStatus()])) {
                $stats[$t->getStatus()]++;
            }
        }

        return Inertia::render('Commerce/Transfers/Index', [
            'transfers' => $transfersData,
            'shops' => $shops,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $tenantId = $this->getTenantId($request);
        $user = $request->user();

        $depotsData = HandleInertiaRequests::getDepotsForRequest($request);
        $availableDepots = $depotsData['depots'] ?? [];

        $depots = [];
        foreach ($availableDepots as $depot) {
            $shop = Shop::query()
                ->where('is_active', true)
                ->when($user?->tenant_id, fn ($q, $tid) => $q->where('tenant_id', $tid))
                ->where('depot_id', $depot['id'])
                ->first();

            if (!$shop) {
                continue;
            }

            $depots[] = [
                'depot_id' => $depot['id'],
                'depot_name' => $depot['name'],
                'depot_code' => $depot['code'] ?? '',
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'shop_code' => $shop->code ?? '',
            ];
        }

        $products = $this->productRepository->search($shopId, '', ['is_active' => true]);
        $productsData = [];
        foreach ($products as $p) {
            $productsData[] = [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'sku' => $p->getSku(),
                'stock' => $p->getStock()->getValue(),
            ];
        }

        return Inertia::render('Commerce/Transfers/Create', [
            'depots' => $depots,
            'products' => $productsData,
            'currentShopId' => $shopId,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $validated = $request->validate([
            'from_shop_id' => 'required|integer|exists:shops,id',
            'to_shop_id' => 'required|integer|exists:shops,id|different:from_shop_id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string|exists:gc_products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            $transfer = $this->transferService->createTransfer(
                $tenantId,
                (int) $validated['from_shop_id'],
                (int) $validated['to_shop_id'],
                $user->id,
                $validated['notes'] ?? null
            );

            foreach ($validated['items'] as $item) {
                $this->transferService->addItem(
                    $transfer->getId(),
                    $item['product_id'],
                    (float) $item['quantity'],
                    $tenantId
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfert créé avec succès',
                'transfer_id' => $transfer->getId(),
                'reference' => $transfer->getReference(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Request $request, string $id): Response
    {
        $shopId = $this->getShopId($request);
        $tenantId = $this->getTenantId($request);

        $transfer = $this->transferService->getTransfer($id, $tenantId);

        if ($transfer === null) {
            abort(404, 'Transfert non trouvé');
        }

        $fromShop = Shop::query()->find($transfer->getFromShopId());
        $toShop = Shop::query()->find($transfer->getToShopId());
        $creator = UserModel::query()->find($transfer->getCreatedBy());
        $validator = $transfer->getValidatedBy() !== null
            ? UserModel::query()->find($transfer->getValidatedBy())
            : null;

        $itemsData = [];
        foreach ($transfer->getItems() as $item) {
            $product = ProductModel::query()->find($item->getProductId());
            $itemsData[] = [
                'id' => $item->getId(),
                'product_id' => $item->getProductId(),
                'product_name' => $product?->name ?? 'Produit inconnu',
                'product_sku' => $product?->sku ?? '',
                'current_stock' => $product ? (float) ($product->stock ?? 0) : 0,
                'quantity' => $item->getQuantity(),
            ];
        }

        $transferData = [
            'id' => $transfer->getId(),
            'reference' => $transfer->getReference(),
            'from_shop_id' => $transfer->getFromShopId(),
            'from_shop_name' => $fromShop?->name ?? 'Magasin inconnu',
            'to_shop_id' => $transfer->getToShopId(),
            'to_shop_name' => $toShop?->name ?? 'Magasin inconnu',
            'status' => $transfer->getStatus(),
            'total_items' => $transfer->getTotalItems(),
            'total_quantity' => $transfer->getTotalQuantity(),
            'created_by_name' => $creator?->name ?? 'Utilisateur inconnu',
            'validated_by_name' => $validator?->name,
            'created_at' => $transfer->getCreatedAt()->format('Y-m-d H:i:s'),
            'created_at_formatted' => $transfer->getCreatedAt()->format('d/m/Y H:i'),
            'validated_at' => $transfer->getValidatedAt()?->format('Y-m-d H:i:s'),
            'validated_at_formatted' => $transfer->getValidatedAt()?->format('d/m/Y H:i'),
            'notes' => $transfer->getNotes(),
            'items' => $itemsData,
        ];

        // Produits du magasin source (pour ajouter des items en mode brouillon)
        $products = $this->productRepository->search($transfer->getFromShopId(), '', ['is_active' => true]);
        $productsData = [];
        foreach ($products as $p) {
            $productsData[] = [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'sku' => $p->getSku(),
                'stock' => $p->getStock()->getValue(),
            ];
        }

        return Inertia::render('Commerce/Transfers/Show', [
            'transfer' => $transferData,
            'products' => $productsData,
        ]);
    }

    public function addItem(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'product_id' => 'required|string|exists:gc_products,id',
            'quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            $item = $this->transferService->addItem(
                $id,
                $validated['product_id'],
                (float) $validated['quantity'],
                $tenantId
            );

            return response()->json([
                'success' => true,
                'message' => 'Produit ajouté au transfert',
                'item_id' => $item->getId(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateItem(Request $request, string $id, string $itemId): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            $this->transferService->updateItemQuantity(
                $itemId,
                (float) $validated['quantity'],
                $tenantId
            );

            return response()->json([
                'success' => true,
                'message' => 'Quantité mise à jour',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function removeItem(Request $request, string $id, string $itemId): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        try {
            $this->transferService->removeItem($itemId, $tenantId);

            return response()->json([
                'success' => true,
                'message' => 'Produit retiré du transfert',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function validate(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        try {
            $transfer = $this->transferService->validateTransfer($id, $user->id, $tenantId);

            return response()->json([
                'success' => true,
                'message' => 'Transfert validé avec succès',
                'reference' => $transfer->getReference(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        try {
            $transfer = $this->transferService->cancelTransfer($id, $tenantId);

            return response()->json([
                'success' => true,
                'message' => 'Transfert annulé',
                'reference' => $transfer->getReference(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
