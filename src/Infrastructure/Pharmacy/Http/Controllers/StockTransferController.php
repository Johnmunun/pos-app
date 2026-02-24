<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Src\Application\Pharmacy\Services\StockTransferService;
use Src\Domain\Pharmacy\Repositories\ProductRepositoryInterface;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Models\StockTransferModel;
use Src\Infrastructure\Pharmacy\Services\PharmacyExportService;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Controller pour la gestion des transferts de stock inter-magasins
 */
class StockTransferController
{
    public function __construct(
        private readonly StockTransferService $transferService,
        private readonly ProductRepositoryInterface $productRepository,
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
        $isRoot = $userModel !== null && $userModel->isRoot();
        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }
        return (string) $shopId;
    }

    /**
     * Vérifie si l'utilisateur est ROOT
     */
    private function isRoot(Request $request): bool
    {
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        /** @var UserModel|null $userModel */
        $userModel = UserModel::query()->find($user->id);
        return $userModel !== null && $userModel->isRoot();
    }

    /**
     * Liste les transferts
     */
    public function index(Request $request): InertiaResponse
    {
        $shopId = $this->getShopId($request);
        $isRoot = $this->isRoot($request);

        $filters = [
            'status' => $request->input('status'),
            'from_shop_id' => $request->input('from_shop_id'),
            'to_shop_id' => $request->input('to_shop_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'reference' => $request->input('reference'),
        ];

        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

        $transfers = $isRoot
            ? $this->transferService->getAllTransfers($filters)
            : $this->transferService->getTransfers($shopId, $filters);

        // Enrichir les données pour le frontend
        $transfersData = [];
        foreach ($transfers as $transfer) {
            /** @var Shop|null $fromShop */
            $fromShop = Shop::query()->find($transfer->getFromShopId());
            /** @var Shop|null $toShop */
            $toShop = Shop::query()->find($transfer->getToShopId());
            /** @var UserModel|null $creator */
            $creator = UserModel::query()->find($transfer->getCreatedBy());
            /** @var UserModel|null $validator */
            $validator = $transfer->getValidatedBy() !== null
                ? UserModel::query()->find($transfer->getValidatedBy())
                : null;

            $fromShopName = $fromShop !== null ? $fromShop->name : 'Magasin inconnu';
            $toShopName = $toShop !== null ? $toShop->name : 'Magasin inconnu';
            $creatorName = $creator !== null ? $creator->name : 'Utilisateur inconnu';
            $validatorName = $validator !== null ? $validator->name : null;

            $transfersData[] = [
                'id' => $transfer->getId(),
                'reference' => $transfer->getReference(),
                'from_shop_id' => $transfer->getFromShopId(),
                'from_shop_name' => $fromShopName,
                'to_shop_id' => $transfer->getToShopId(),
                'to_shop_name' => $toShopName,
                'status' => $transfer->getStatus(),
                'total_items' => $transfer->getTotalItems(),
                'total_quantity' => $transfer->getTotalQuantity(),
                'created_by_name' => $creatorName,
                'validated_by_name' => $validatorName,
                'created_at' => $transfer->getCreatedAt()->format('Y-m-d H:i:s'),
                'created_at_formatted' => $transfer->getCreatedAt()->format('d/m/Y H:i'),
                'validated_at' => $transfer->getValidatedAt()?->format('Y-m-d H:i:s'),
                'validated_at_formatted' => $transfer->getValidatedAt()?->format('d/m/Y H:i'),
                'notes' => $transfer->getNotes(),
            ];
        }

        // Récupérer les magasins pour les filtres
        /** @var \Illuminate\Database\Eloquent\Collection<int, Shop> $shopsCollection */
        $shopsCollection = Shop::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $shops = [];
        foreach ($shopsCollection as $shop) {
            $shops[] = [
                'id' => $shop->id,
                'name' => $shop->name,
                'code' => $shop->code,
            ];
        }

        // Statistiques
        $stats = [
            'total' => count($transfers),
            'draft' => 0,
            'validated' => 0,
            'cancelled' => 0,
        ];

        foreach ($transfers as $t) {
            $status = $t->getStatus();
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return Inertia::render('Pharmacy/Transfers/Index', [
            'transfers' => $transfersData,
            'shops' => $shops,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * Affiche le formulaire de création
     */
    public function create(Request $request): InertiaResponse
    {
        $shopId = $this->getShopId($request);

        // Récupérer les magasins actifs
        /** @var \Illuminate\Database\Eloquent\Collection<int, Shop> $shopsCollection */
        $shopsCollection = Shop::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $shops = [];
        foreach ($shopsCollection as $shop) {
            $shops[] = [
                'id' => $shop->id,
                'name' => $shop->name,
                'code' => $shop->code,
            ];
        }

        // Récupérer les produits
        $products = $this->productRepository->findByShop($shopId, ['active' => true]);
        $productsData = [];
        foreach ($products as $product) {
            $productsData[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'code' => $product->getCode(),
                'stock' => $product->getStock()->getValue(),
            ];
        }

        return Inertia::render('Pharmacy/Transfers/Create', [
            'shops' => $shops,
            'products' => $productsData,
            'currentShopId' => $shopId,
        ]);
    }

    /**
     * Crée un nouveau transfert
     */
    public function store(Request $request): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $validated = $request->validate([
            'from_shop_id' => 'required|integer|exists:shops,id',
            'to_shop_id' => 'required|integer|exists:shops,id|different:from_shop_id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string|exists:pharmacy_products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $transfer = $this->transferService->createTransfer(
                $shopId,
                (int) $validated['from_shop_id'],
                (int) $validated['to_shop_id'],
                $user->id,
                $validated['notes'] ?? null
            );

            // Ajouter les items
            foreach ($validated['items'] as $item) {
                $this->transferService->addItem(
                    $transfer->getId(),
                    $item['product_id'],
                    $item['quantity'],
                    $shopId
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

    /**
     * Affiche le détail d'un transfert
     */
    public function show(Request $request, string $id): InertiaResponse
    {
        $shopId = $this->getShopId($request);
        $isRoot = $this->isRoot($request);

        $transfer = $isRoot
            ? $this->transferService->getTransfer($id, $shopId)
            : $this->transferService->getTransfer($id, $shopId);

        if ($transfer === null) {
            abort(404, 'Transfert non trouvé');
        }

        // Enrichir les données
        /** @var Shop|null $fromShop */
        $fromShop = Shop::query()->find($transfer->getFromShopId());
        /** @var Shop|null $toShop */
        $toShop = Shop::query()->find($transfer->getToShopId());
        /** @var UserModel|null $creator */
        $creator = UserModel::query()->find($transfer->getCreatedBy());
        /** @var UserModel|null $validator */
        $validator = $transfer->getValidatedBy() !== null
            ? UserModel::query()->find($transfer->getValidatedBy())
            : null;

        $fromShopName = $fromShop !== null ? $fromShop->name : 'Magasin inconnu';
        $toShopName = $toShop !== null ? $toShop->name : 'Magasin inconnu';
        $creatorName = $creator !== null ? $creator->name : 'Utilisateur inconnu';
        $validatorName = $validator !== null ? $validator->name : null;

        // Enrichir les items
        $itemsData = [];
        foreach ($transfer->getItems() as $item) {
            /** @var ProductModel|null $product */
            $product = ProductModel::query()->find($item->getProductId());

            $productName = $product !== null ? $product->name : 'Produit inconnu';
            $productCode = $product !== null ? ($product->code ?? '') : '';
            $productStock = $product !== null ? ($product->stock ?? 0) : 0;

            $itemsData[] = [
                'id' => $item->getId(),
                'product_id' => $item->getProductId(),
                'product_name' => $productName,
                'product_code' => $productCode,
                'current_stock' => $productStock,
                'quantity' => $item->getQuantity(),
            ];
        }

        $transferData = [
            'id' => $transfer->getId(),
            'reference' => $transfer->getReference(),
            'from_shop_id' => $transfer->getFromShopId(),
            'from_shop_name' => $fromShopName,
            'to_shop_id' => $transfer->getToShopId(),
            'to_shop_name' => $toShopName,
            'status' => $transfer->getStatus(),
            'total_items' => $transfer->getTotalItems(),
            'total_quantity' => $transfer->getTotalQuantity(),
            'created_by_name' => $creatorName,
            'validated_by_name' => $validatorName,
            'created_at' => $transfer->getCreatedAt()->format('Y-m-d H:i:s'),
            'created_at_formatted' => $transfer->getCreatedAt()->format('d/m/Y H:i'),
            'validated_at' => $transfer->getValidatedAt()?->format('Y-m-d H:i:s'),
            'validated_at_formatted' => $transfer->getValidatedAt()?->format('d/m/Y H:i'),
            'notes' => $transfer->getNotes(),
            'items' => $itemsData,
        ];

        // Récupérer les produits pour l'ajout
        $products = $this->productRepository->findByShop($shopId, ['active' => true]);
        $productsData = [];
        foreach ($products as $product) {
            $productsData[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'code' => $product->getCode(),
                'stock' => $product->getStock()->getValue(),
            ];
        }

        return Inertia::render('Pharmacy/Transfers/Show', [
            'transfer' => $transferData,
            'products' => $productsData,
        ]);
    }

    /**
     * Ajoute un item au transfert
     */
    public function addItem(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'product_id' => 'required|string|exists:pharmacy_products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $item = $this->transferService->addItem(
                $id,
                $validated['product_id'],
                $validated['quantity'],
                $shopId
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

    /**
     * Met à jour la quantité d'un item
     */
    public function updateItem(Request $request, string $id, string $itemId): JsonResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->transferService->updateItemQuantity(
                $itemId,
                $validated['quantity'],
                $shopId
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

    /**
     * Supprime un item du transfert
     */
    public function removeItem(Request $request, string $id, string $itemId): JsonResponse
    {
        $shopId = $this->getShopId($request);

        try {
            $this->transferService->removeItem($itemId, $shopId);

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

    /**
     * Valide un transfert
     */
    public function validate(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        try {
            $transfer = $this->transferService->validateTransfer($id, $user->id, $shopId);

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

    /**
     * Annule un transfert
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $shopId = $this->getShopId($request);

        try {
            $transfer = $this->transferService->cancelTransfer($id, $shopId);

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

    /**
     * Exporte un transfert en PDF
     */
    public function exportPdf(Request $request, string $id): Response
    {
        $shopId = $this->getShopId($request);

        $transfer = $this->transferService->getTransfer($id, $shopId);

        if ($transfer === null) {
            abort(404, 'Transfert non trouvé');
        }

        // Enrichir les données
        /** @var Shop|null $fromShop */
        $fromShop = Shop::query()->find($transfer->getFromShopId());
        /** @var Shop|null $toShop */
        $toShop = Shop::query()->find($transfer->getToShopId());
        /** @var UserModel|null $creator */
        $creator = UserModel::query()->find($transfer->getCreatedBy());
        /** @var UserModel|null $validator */
        $validator = $transfer->getValidatedBy() !== null
            ? UserModel::query()->find($transfer->getValidatedBy())
            : null;

        $fromShopName = $fromShop !== null ? $fromShop->name : 'Magasin inconnu';
        $fromShopAddress = $fromShop !== null ? ($fromShop->address ?? '') : '';
        $toShopName = $toShop !== null ? $toShop->name : 'Magasin inconnu';
        $toShopAddress = $toShop !== null ? ($toShop->address ?? '') : '';
        $creatorName = $creator !== null ? $creator->name : 'Utilisateur inconnu';
        $validatorName = $validator !== null ? $validator->name : null;

        // Enrichir les items
        $itemsData = [];
        foreach ($transfer->getItems() as $item) {
            /** @var ProductModel|null $product */
            $product = ProductModel::query()->find($item->getProductId());

            $productName = $product !== null ? $product->name : 'Produit inconnu';
            $productCode = $product !== null ? ($product->code ?? '') : '';

            $itemsData[] = [
                'product_name' => $productName,
                'product_code' => $productCode,
                'quantity' => $item->getQuantity(),
            ];
        }

        $header = $this->exportService->getExportHeader($request);

        $data = [
            'header' => $header,
            'transfer' => [
                'reference' => $transfer->getReference(),
                'status' => $transfer->getStatus(),
                'status_label' => $this->getStatusLabel($transfer->getStatus()),
                'from_shop_name' => $fromShopName,
                'from_shop_address' => $fromShopAddress,
                'to_shop_name' => $toShopName,
                'to_shop_address' => $toShopAddress,
                'total_items' => $transfer->getTotalItems(),
                'total_quantity' => $transfer->getTotalQuantity(),
                'created_by_name' => $creatorName,
                'validated_by_name' => $validatorName,
                'created_at' => $transfer->getCreatedAt()->format('d/m/Y H:i'),
                'validated_at' => $transfer->getValidatedAt()?->format('d/m/Y H:i'),
                'notes' => $transfer->getNotes(),
            ],
            'items' => $itemsData,
        ];

        $pdf = Pdf::loadView('pharmacy.exports.transfer', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'transfert_' . $transfer->getReference() . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Retourne le libellé du statut
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Brouillon',
            'validated' => 'Validé',
            'cancelled' => 'Annulé',
            default => $status,
        };
    }
}
