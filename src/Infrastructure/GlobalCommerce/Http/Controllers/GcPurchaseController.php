<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;
use Src\Application\GlobalCommerce\Procurement\DTO\CreatePurchaseDTO;
use Src\Application\GlobalCommerce\Procurement\UseCases\CreatePurchaseUseCase;
use Src\Application\GlobalCommerce\Procurement\UseCases\ReceivePurchaseUseCase;
use Src\Domain\GlobalCommerce\Procurement\Repositories\PurchaseRepositoryInterface;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\SupplierModel;

class GcPurchaseController
{
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

    public function __construct(
        private PurchaseRepositoryInterface $purchaseRepository,
        private ProductRepositoryInterface $productRepository,
        private CreatePurchaseUseCase $createPurchaseUseCase,
        private ReceivePurchaseUseCase $receivePurchaseUseCase
    ) {
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        
        // Log pour déboguer
        \Log::debug('GcPurchaseController::index - Récupération des achats', [
            'shop_id' => $shopId,
            'shop_id_type' => gettype($shopId),
        ]);
        
        // Convertir shop_id en entier si nécessaire (car la colonne est unsignedBigInteger)
        $shopIdInt = (int) $shopId;
        
        $models = PurchaseModel::with(['lines', 'supplier'])
            ->where('shop_id', $shopIdInt)
            ->orderByDesc('created_at')
            ->limit(50)
            ->offset(0)
            ->get();
        
        \Log::debug('GcPurchaseController::index - Achats trouvés', [
            'count' => $models->count(),
            'models' => $models->map(fn ($m) => [
                'id' => $m->id,
                'shop_id' => $m->shop_id,
                'supplier_id' => $m->supplier_id,
                'status' => $m->status,
            ])->toArray(),
        ]);

        $list = $models->map(fn ($m) => [
            'id' => $m->id,
            'supplier_id' => $m->supplier_id,
            'supplier_name' => $m->supplier?->name ?? '—',
            'status' => $m->status,
            'total_amount' => (float) $m->total_amount,
            'currency' => $m->currency,
            'expected_at' => $m->expected_at?->format('Y-m-d'),
            'received_at' => $m->received_at?->format('d/m/Y H:i'),
            'created_at' => $m->created_at->format('d/m/Y H:i'),
            'lines_count' => $m->lines->count(),
        ])->values()->all();
        
        \Log::debug('GcPurchaseController::index - Liste formatée', [
            'count' => count($list),
            'first_item' => $list[0] ?? null,
        ]);

        // Récupérer les fournisseurs pour le drawer
        $suppliers = SupplierModel::byShop($shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone,
            ])->values()->all();

        // Récupérer les produits pour le drawer
        $products = $this->productRepository->search($shopId, '', ['is_active' => true]);
        $productsList = array_map(fn ($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'sku' => $p->getSku(),
            'cost_amount' => $p->getPurchasePrice()->getAmount(),
        ], $products);

        return Inertia::render('Commerce/Purchases/Index', [
            'purchases' => $list,
            'purchase_orders' => $list, // Alias pour compatibilité avec le composant React
            'suppliers' => $suppliers,
            'products' => $productsList,
            'filters' => $request->only(['from', 'to', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $suppliers = SupplierModel::byShop($shopId)->where('is_active', true)->orderBy('name')->get();
        $products = $this->productRepository->search($shopId, '', ['is_active' => true]);
        return Inertia::render('Commerce/Purchases/Create', [
            'suppliers' => $suppliers->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
            'products' => array_map(fn ($p) => [
                'id' => $p->getId(),
                'sku' => $p->getSku(),
                'name' => $p->getName(),
                'currency' => $p->getPurchasePrice()->getCurrency(),
            ], $products),
            'currency' => $products[0]->getPurchasePrice()->getCurrency() ?? 'USD',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        
        // Log pour déboguer
        $linesInput = $request->input('lines', []);
        \Log::debug('GcPurchaseController::store - Données reçues', [
            'lines' => $linesInput,
            'lines_count' => count($linesInput),
            'first_line' => $linesInput[0] ?? null,
            'first_line_keys' => $linesInput[0] ? array_keys($linesInput[0]) : [],
            'first_line_quantity' => $linesInput[0]['quantity'] ?? 'MISSING',
            'request_all' => $request->all(),
        ]);
        
        // Nettoyer et valider les lignes avant validation Laravel
        $cleanedLines = [];
        foreach ($linesInput as $index => $line) {
            if (!is_array($line)) {
                \Log::warning("Ligne {$index} n'est pas un tableau", ['line' => $line]);
                continue;
            }
            
            // Accepter 'quantity' ou 'ordered_quantity' (pour compatibilité avec le frontend)
            $quantityValue = $line['quantity'] ?? $line['ordered_quantity'] ?? null;
            if ($quantityValue === null || $quantityValue === '') {
                \Log::warning("Ligne {$index} n'a pas de quantity/ordered_quantity", [
                    'line' => $line, 
                    'keys' => array_keys($line),
                    'has_quantity' => isset($line['quantity']),
                    'has_ordered_quantity' => isset($line['ordered_quantity']),
                ]);
                continue;
            }
            
            $quantity = (float) $quantityValue;
            if ($quantity <= 0 || is_nan($quantity)) {
                \Log::warning("Ligne {$index} a une quantity invalide", [
                    'quantity' => $quantityValue, 
                    'quantity_float' => $quantity,
                    'line' => $line
                ]);
                continue;
            }
            
            // S'assurer que unit_cost existe et est valide
            if (!isset($line['unit_cost']) || $line['unit_cost'] === null || $line['unit_cost'] === '') {
                \Log::warning("Ligne {$index} n'a pas de unit_cost", ['line' => $line]);
                continue;
            }
            
            $unitCost = (float) $line['unit_cost'];
            if ($unitCost < 0 || is_nan($unitCost)) {
                \Log::warning("Ligne {$index} a un unit_cost invalide", ['unit_cost' => $line['unit_cost'], 'line' => $line]);
                continue;
            }
            
            // Vérifier que product_id existe
            if (!isset($line['product_id']) || $line['product_id'] === null || $line['product_id'] === '') {
                \Log::warning("Ligne {$index} n'a pas de product_id", ['line' => $line]);
                continue;
            }
            
            // Créer une ligne propre avec 'quantity' (le backend attend toujours 'quantity')
            $cleanedLine = [
                'product_id' => $line['product_id'],
                'quantity' => $quantity, // Toujours utiliser 'quantity' pour le backend
                'unit_cost' => $unitCost,
            ];
            
            $cleanedLines[] = $cleanedLine;
        }
        
        // Remplacer les lignes dans la requête
        $request->merge(['lines' => $cleanedLines]);
        
        if (empty($cleanedLines)) {
            \Log::error('Aucune ligne valide après nettoyage', [
                'original_lines' => $linesInput,
                'cleaned_lines' => $cleanedLines,
            ]);
            return redirect()->back()->withErrors(['lines' => 'Aucune ligne valide après nettoyage.'])->withInput();
        }
        
        $validated = $request->validate([
            'supplier_id' => 'required|uuid|exists:gc_suppliers,id',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|uuid|exists:gc_products,id',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_cost' => 'required|numeric|min:0',
            'expected_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $lines = array_values(array_filter($validated['lines'], fn ($l) => ((float) ($l['quantity'] ?? 0)) > 0 && ((float) ($l['unit_cost'] ?? 0)) >= 0));
        if (empty($lines)) {
            return redirect()->back()->withErrors(['lines' => 'Au moins une ligne avec quantité et coût unitaire.'])->withInput();
        }
        $dto = new CreatePurchaseDTO(
            $shopId,
            $validated['supplier_id'],
            array_map(fn ($l) => [
                'product_id' => $l['product_id'],
                'quantity' => (float) $l['quantity'],
                'unit_cost' => (float) $l['unit_cost'],
            ], $lines),
            $request->input('currency', 'USD'),
            $validated['expected_at'] ?? null,
            $validated['notes'] ?? null
        );
        try {
            \Log::debug('GcPurchaseController::store - Avant création', [
                'shop_id' => $shopId,
                'shop_id_type' => gettype($shopId),
                'supplier_id' => $validated['supplier_id'],
                'lines_count' => count($cleanedLines),
                'cleaned_lines' => $cleanedLines,
            ]);
            
            $purchase = $this->createPurchaseUseCase->execute($dto);
            
            \Log::debug('GcPurchaseController::store - Après création', [
                'purchase_id' => $purchase->getId(),
                'purchase_shop_id' => $purchase->getShopId(),
            ]);
            
            // Vérifier que l'achat a bien été sauvegardé en base de données
            $savedPurchase = PurchaseModel::with('lines')->find($purchase->getId());
            if ($savedPurchase) {
                \Log::debug('GcPurchaseController::store - Achat sauvegardé avec succès', [
                    'id' => $savedPurchase->id,
                    'shop_id' => $savedPurchase->shop_id,
                    'shop_id_type' => gettype($savedPurchase->shop_id),
                    'lines_count' => $savedPurchase->lines->count(),
                    'status' => $savedPurchase->status,
                ]);
            } else {
                \Log::error('GcPurchaseController::store - Achat non trouvé après sauvegarde', [
                    'purchase_id' => $purchase->getId(),
                ]);
                return redirect()->back()
                    ->withErrors(['error' => 'Le bon de commande a été créé mais n\'a pas pu être sauvegardé. Veuillez réessayer.'])
                    ->withInput();
            }
            
            return redirect()->route('commerce.purchases.index')->with('success', 'Bon de commande créé.');
        } catch (\InvalidArgumentException $e) {
            \Log::error('GcPurchaseController::store - Erreur de validation', [
                'message' => $e->getMessage(),
                'shop_id' => $shopId,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            \Log::error('GcPurchaseController::store - Erreur inattendue', [
                'message' => $e->getMessage(),
                'shop_id' => $shopId,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()
                ->withErrors(['error' => 'Une erreur est survenue lors de la création du bon de commande: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Request $request, string $id): Response|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $purchase = $this->purchaseRepository->findById($id);
        if (!$purchase || $purchase->getShopId() !== $shopId) {
            return redirect()->route('commerce.purchases.index')->with('error', 'Bon de commande introuvable.');
        }
        $supplier = SupplierModel::find($purchase->getSupplierId());
        $lines = array_map(fn ($l) => [
            'product_name' => $l['product_name'],
            'ordered_quantity' => $l['ordered_quantity'],
            'received_quantity' => $l['received_quantity'],
            'unit_cost' => $l['unit_cost'],
            'line_total' => $l['line_total'],
        ], $purchase->getLines());
        return Inertia::render('Commerce/Purchases/Show', [
            'purchase' => [
                'id' => $purchase->getId(),
                'supplier_name' => $supplier?->name ?? '—',
                'status' => $purchase->getStatus(),
                'total_amount' => $purchase->getTotalAmount(),
                'currency' => $purchase->getCurrency(),
                'expected_at' => $purchase->getExpectedAt()?->format('Y-m-d'),
                'received_at' => $purchase->getReceivedAt()?->format('d/m/Y H:i'),
                'notes' => $purchase->getNotes(),
                'created_at' => $purchase->getCreatedAt()->format('d/m/Y H:i'),
                'lines' => $lines,
            ],
        ]);
    }

    public function receive(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        if ($user && !$user->hasPermission('commerce.purchases.receive') && $user->type !== 'ROOT') {
            abort(403, 'Vous n\'avez pas le droit de réceptionner un bon de commande.');
        }
        $shopId = $this->getShopId($request);
        try {
            $this->receivePurchaseUseCase->execute($shopId, $id);
            return redirect()->route('commerce.purchases.show', $id)->with('success', 'Bon réceptionné. Stock mis à jour.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
