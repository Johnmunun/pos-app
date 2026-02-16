<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Src\Infrastructure\Pharmacy\Services\PharmacyExportService;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Models\SupplierModel;
use Src\Infrastructure\Pharmacy\Models\CustomerModel;
use Src\Infrastructure\Pharmacy\Models\SaleModel;
use Src\Infrastructure\Pharmacy\Models\PurchaseOrderModel;
use Src\Infrastructure\Pharmacy\Models\StockMovementModel;
use Src\Infrastructure\Pharmacy\Models\ProductBatchModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contrôleur centralisé pour les exports PDF et Excel du module Pharmacy.
 */
class ExportController extends Controller
{
    public function __construct(
        private readonly PharmacyExportService $exportService
    ) {
    }

    // ========== STOCK ==========

    /**
     * Export PDF du stock.
     */
    public function stockPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];
        $currency = $header['currency'];

        $query = ProductModel::query()->with('category')->where('is_active', true)->orderBy('name');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        $products = $query->get();

        $items = $products->map(fn($p) => [
            'code' => $p->code ?? '',
            'name' => $p->name,
            'category' => $p->category?->name ?? '',
            'stock' => (int) ($p->stock ?? 0),
            'low_stock_threshold' => (int) ($p->low_stock_threshold ?? 10),
            'price' => (float) ($p->price_amount ?? 0),
            'stock_value' => (float) ($p->stock ?? 0) * (float) ($p->price_amount ?? 0),
        ])->toArray();

        $summary = [
            'total_products' => count($items),
            'low_stock' => collect($items)->filter(fn($i) => $i['stock'] > 0 && $i['stock'] <= $i['low_stock_threshold'])->count(),
            'out_of_stock' => collect($items)->filter(fn($i) => $i['stock'] <= 0)->count(),
            'total_value' => collect($items)->sum('stock_value'),
        ];

        return $this->exportService->exportPdf('pharmacy.exports.stock', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'stock', 'landscape');
    }

    /**
     * Export Excel du stock.
     */
    public function stockExcel(Request $request): StreamedResponse
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];
        $currency = $header['currency'];

        $query = ProductModel::query()->with('category')->where('is_active', true)->orderBy('name');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        $products = $query->get();

        $columns = ['#', 'Code', 'Produit', 'Catégorie', 'Stock', 'Seuil alerte', 'Prix unitaire', 'Valeur stock'];
        $rows = [];
        $index = 1;

        foreach ($products as $p) {
            $stockValue = (float) ($p->stock ?? 0) * (float) ($p->price_amount ?? 0);
            $rows[] = [
                $index++,
                $p->code ?? '',
                $p->name,
                $p->category?->name ?? '',
                (int) ($p->stock ?? 0),
                (int) ($p->low_stock_threshold ?? 10),
                number_format((float) ($p->price_amount ?? 0), 2) . ' ' . $currency,
                number_format($stockValue, 2) . ' ' . $currency,
            ];
        }

        return $this->exportService->exportExcel(
            $header,
            'État du Stock',
            $columns,
            $rows,
            'stock',
            ['E' => 'right', 'F' => 'right', 'G' => 'right', 'H' => 'right']
        );
    }

    // ========== VENTES ==========

    /**
     * Export PDF des ventes.
     */
    public function salesPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];
        $currency = $header['currency'];

        $query = SaleModel::query()->with('customer')->orderByDesc('created_at');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        // Filtres de date
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $sales = $query->get();

        $items = $sales->map(fn($s) => [
            'reference' => $s->reference ?? 'SALE-' . $s->id,
            'date' => $s->created_at?->format('d/m/Y H:i') ?? '',
            'customer' => $s->customer?->name ?? 'Client comptoir',
            'items_count' => $s->items_count ?? 0,
            'total' => (float) ($s->total_amount ?? 0),
            'paid' => (float) ($s->paid_amount ?? 0),
            'status' => $s->status ?? 'pending',
        ])->toArray();

        $summary = [
            'total_sales' => count($items),
            'paid_count' => collect($items)->filter(fn($i) => in_array($i['status'], ['paid', 'completed']))->count(),
            'pending_count' => collect($items)->filter(fn($i) => $i['status'] === 'pending')->count(),
            'total_amount' => collect($items)->sum('total'),
            'total_paid' => collect($items)->sum('paid'),
        ];

        return $this->exportService->exportPdf('pharmacy.exports.sales', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
            'filters' => $request->only(['from', 'to']),
        ], 'ventes');
    }

    /**
     * Export Excel des ventes.
     */
    public function salesExcel(Request $request): StreamedResponse
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];
        $currency = $header['currency'];

        $query = SaleModel::query()->with('customer')->orderByDesc('created_at');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $sales = $query->get();

        $columns = ['#', 'Référence', 'Date', 'Client', 'Articles', 'Total', 'Payé', 'Statut'];
        $rows = [];
        $index = 1;

        $statusLabels = [
            'paid' => 'Payé', 'completed' => 'Payé', 'partial' => 'Partiel',
            'pending' => 'En attente', 'cancelled' => 'Annulé'
        ];

        foreach ($sales as $s) {
            $rows[] = [
                $index++,
                $s->reference ?? 'SALE-' . $s->id,
                $s->created_at?->format('d/m/Y H:i') ?? '',
                $s->customer?->name ?? 'Client comptoir',
                $s->items_count ?? 0,
                number_format((float) ($s->total_amount ?? 0), 2) . ' ' . $currency,
                number_format((float) ($s->paid_amount ?? 0), 2) . ' ' . $currency,
                $statusLabels[$s->status ?? 'pending'] ?? $s->status,
            ];
        }

        return $this->exportService->exportExcel(
            $header,
            'Liste des Ventes',
            $columns,
            $rows,
            'ventes',
            ['F' => 'right', 'G' => 'right', 'H' => 'center']
        );
    }

    // ========== ACHATS ==========

    /**
     * Export PDF des achats.
     */
    public function purchasesPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];
        $currency = $header['currency'];

        $query = PurchaseOrderModel::query()->with('supplier')->orderByDesc('created_at');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $purchases = $query->get();

        $items = $purchases->map(fn($p) => [
            'reference' => $p->reference ?? 'PO-' . $p->id,
            'date' => $p->created_at?->format('d/m/Y') ?? '',
            'supplier' => $p->supplier?->name ?? '',
            'items_count' => $p->items_count ?? 0,
            'total' => (float) ($p->total_amount ?? 0),
            'status' => $p->status ?? 'draft',
            'expected_date' => $p->expected_at?->format('d/m/Y') ?? '',
        ])->toArray();

        $summary = [
            'total_orders' => count($items),
            'received_count' => collect($items)->filter(fn($i) => in_array($i['status'], ['received', 'completed']))->count(),
            'pending_count' => collect($items)->filter(fn($i) => in_array($i['status'], ['draft', 'ordered']))->count(),
            'total_amount' => collect($items)->sum('total'),
        ];

        return $this->exportService->exportPdf('pharmacy.exports.purchases', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
            'filters' => $request->only(['from', 'to']),
        ], 'achats');
    }

    /**
     * Export Excel des achats.
     */
    public function purchasesExcel(Request $request): StreamedResponse
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];
        $currency = $header['currency'];

        $query = PurchaseOrderModel::query()->with('supplier')->orderByDesc('created_at');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $purchases = $query->get();

        $columns = ['#', 'Référence', 'Date', 'Fournisseur', 'Articles', 'Montant', 'Statut', 'Livraison prévue'];
        $rows = [];
        $index = 1;

        $statusLabels = [
            'received' => 'Reçu', 'completed' => 'Reçu', 'partial' => 'Partiel',
            'ordered' => 'Commandé', 'draft' => 'Brouillon', 'cancelled' => 'Annulé'
        ];

        foreach ($purchases as $p) {
            $rows[] = [
                $index++,
                $p->reference ?? 'PO-' . $p->id,
                $p->created_at?->format('d/m/Y') ?? '',
                $p->supplier?->name ?? '',
                $p->items_count ?? 0,
                number_format((float) ($p->total_amount ?? 0), 2) . ' ' . $currency,
                $statusLabels[$p->status ?? 'draft'] ?? $p->status,
                $p->expected_at?->format('d/m/Y') ?? '',
            ];
        }

        return $this->exportService->exportExcel(
            $header,
            'Liste des Achats',
            $columns,
            $rows,
            'achats',
            ['F' => 'right', 'G' => 'center']
        );
    }

    // ========== FOURNISSEURS ==========

    /**
     * Export PDF des fournisseurs.
     */
    public function suppliersPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];

        $query = SupplierModel::query()->orderBy('name');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        $suppliers = $query->get();

        $items = $suppliers->map(fn($s) => [
            'name' => $s->name,
            'contact_person' => $s->contact_person ?? '',
            'phone' => $s->phone ?? '',
            'email' => $s->email ?? '',
            'total_orders' => $s->total_orders ?? 0,
            'status' => $s->status ?? 'active',
        ])->toArray();

        $summary = [
            'total' => count($items),
            'active' => collect($items)->filter(fn($i) => $i['status'] === 'active')->count(),
            'inactive' => collect($items)->filter(fn($i) => $i['status'] !== 'active')->count(),
        ];

        return $this->exportService->exportPdf('pharmacy.exports.suppliers', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'fournisseurs');
    }

    /**
     * Export Excel des fournisseurs.
     */
    public function suppliersExcel(Request $request): StreamedResponse
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];

        $query = SupplierModel::query()->orderBy('name');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        $suppliers = $query->get();

        $columns = ['#', 'Nom', 'Contact', 'Téléphone', 'Email', 'Commandes', 'Statut'];
        $rows = [];
        $index = 1;

        foreach ($suppliers as $s) {
            $rows[] = [
                $index++,
                $s->name,
                $s->contact_person ?? '',
                $s->phone ?? '',
                $s->email ?? '',
                $s->total_orders ?? 0,
                ($s->status ?? 'active') === 'active' ? 'Actif' : 'Inactif',
            ];
        }

        return $this->exportService->exportExcel(
            $header,
            'Liste des Fournisseurs',
            $columns,
            $rows,
            'fournisseurs',
            ['F' => 'center', 'G' => 'center']
        );
    }

    // ========== CLIENTS ==========

    /**
     * Export PDF des clients.
     */
    public function customersPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];

        $query = CustomerModel::query()->orderBy('name');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        $customers = $query->get();

        $items = $customers->map(fn($c) => [
            'name' => $c->name,
            'phone' => $c->phone ?? '',
            'email' => $c->email ?? '',
            'address' => $c->address ?? '',
            'balance' => (float) ($c->balance ?? 0),
        ])->toArray();

        $summary = [
            'total' => count($items),
            'with_credit' => collect($items)->filter(fn($i) => $i['balance'] > 0)->count(),
            'total_credit' => collect($items)->sum('balance'),
        ];

        return $this->exportService->exportPdf('pharmacy.exports.customers', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'clients');
    }

    /**
     * Export Excel des clients.
     */
    public function customersExcel(Request $request): StreamedResponse
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];
        $currency = $header['currency'];

        $query = CustomerModel::query()->orderBy('name');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        $customers = $query->get();

        $columns = ['#', 'Nom', 'Téléphone', 'Email', 'Adresse', 'Solde'];
        $rows = [];
        $index = 1;

        foreach ($customers as $c) {
            $rows[] = [
                $index++,
                $c->name,
                $c->phone ?? '',
                $c->email ?? '',
                $c->address ?? '',
                number_format((float) ($c->balance ?? 0), 2) . ' ' . $currency,
            ];
        }

        return $this->exportService->exportExcel(
            $header,
            'Liste des Clients',
            $columns,
            $rows,
            'clients',
            ['F' => 'right']
        );
    }

    // ========== EXPIRATIONS ==========

    /**
     * Export PDF des expirations.
     */
    public function expirationsPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];
        $currency = $header['currency'];

        $query = ProductBatchModel::query()
            ->with('product')
            ->where('quantity', '>', 0)
            ->orderBy('expiration_date');

        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        $batches = $query->get();

        $items = $batches->map(function ($b) use ($currency) {
            $expirationDate = $b->expiration_date;
            $daysUntilExpiry = $expirationDate ? (int) now()->diffInDays($expirationDate, false) : 0;
            $value = (float) ($b->quantity ?? 0) * (float) ($b->product?->price_amount ?? 0);

            $status = 'ok';
            if ($daysUntilExpiry < 0) {
                $status = 'expired';
            } elseif ($daysUntilExpiry <= 30) {
                $status = 'expiring_soon';
            }

            return [
                'product_name' => $b->product?->name ?? '',
                'product_code' => $b->product?->code ?? '',
                'batch_number' => $b->batch_number ?? '',
                'quantity' => (int) ($b->quantity ?? 0),
                'expiration_date' => $expirationDate?->format('d/m/Y') ?? '',
                'days_until_expiry' => $daysUntilExpiry,
                'status' => $status,
                'value' => $value,
            ];
        })->toArray();

        $summary = [
            'total_batches' => count($items),
            'expired' => collect($items)->filter(fn($i) => $i['status'] === 'expired')->count(),
            'expiring_soon' => collect($items)->filter(fn($i) => $i['status'] === 'expiring_soon')->count(),
            'expired_value' => collect($items)->filter(fn($i) => $i['status'] === 'expired')->sum('value'),
            'expiring_value' => collect($items)->filter(fn($i) => $i['status'] === 'expiring_soon')->sum('value'),
            'at_risk_value' => collect($items)->filter(fn($i) => in_array($i['status'], ['expired', 'expiring_soon']))->sum('value'),
        ];

        return $this->exportService->exportPdf('pharmacy.exports.expirations', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'expirations');
    }

    /**
     * Export Excel des expirations.
     */
    public function expirationsExcel(Request $request): StreamedResponse
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];
        $currency = $header['currency'];

        $query = ProductBatchModel::query()
            ->with('product')
            ->where('quantity', '>', 0)
            ->orderBy('expiration_date');

        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        $batches = $query->get();

        $columns = ['#', 'Produit', 'Code', 'N° Lot', 'Quantité', 'Expiration', 'Jours restants', 'Statut', 'Valeur'];
        $rows = [];
        $index = 1;

        $statusLabels = ['expired' => 'Expiré', 'expiring_soon' => 'Expire bientôt', 'ok' => 'OK'];

        foreach ($batches as $b) {
            $expirationDate = $b->expiration_date;
            $daysUntilExpiry = $expirationDate ? (int) now()->diffInDays($expirationDate, false) : 0;
            $value = (float) ($b->quantity ?? 0) * (float) ($b->product?->price_amount ?? 0);

            $status = 'ok';
            if ($daysUntilExpiry < 0) {
                $status = 'expired';
            } elseif ($daysUntilExpiry <= 30) {
                $status = 'expiring_soon';
            }

            $rows[] = [
                $index++,
                $b->product?->name ?? '',
                $b->product?->code ?? '',
                $b->batch_number ?? '',
                (int) ($b->quantity ?? 0),
                $expirationDate?->format('d/m/Y') ?? '',
                $daysUntilExpiry < 0 ? abs($daysUntilExpiry) . 'j dépassé' : $daysUntilExpiry . 'j',
                $statusLabels[$status] ?? $status,
                number_format($value, 2) . ' ' . $currency,
            ];
        }

        return $this->exportService->exportExcel(
            $header,
            'Rapport des Expirations',
            $columns,
            $rows,
            'expirations',
            ['E' => 'center', 'F' => 'center', 'G' => 'center', 'H' => 'center', 'I' => 'right']
        );
    }

    // ========== MOUVEMENTS ==========

    /**
     * Export PDF des mouvements de stock.
     */
    public function movementsPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];

        $query = StockMovementModel::query()->with('product')->orderByDesc('created_at');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $movements = $query->get();

        $items = $movements->map(fn($m) => [
            'date' => $m->created_at?->format('d/m/Y H:i') ?? '',
            'product_name' => $m->product?->name ?? '',
            'product_code' => $m->product?->code ?? '',
            'type' => $m->type ?? 'adjustment',
            'quantity' => (int) ($m->quantity ?? 0),
            'stock_before' => (int) ($m->stock_before ?? 0),
            'stock_after' => (int) ($m->stock_after ?? 0),
            'reason' => $m->reason ?? '',
        ])->toArray();

        $totalIn = collect($items)->filter(fn($i) => $i['quantity'] > 0)->sum('quantity');
        $totalOut = collect($items)->filter(fn($i) => $i['quantity'] < 0)->sum(fn($i) => abs($i['quantity']));

        $summary = [
            'total_movements' => count($items),
            'total_in' => $totalIn,
            'total_out' => $totalOut,
        ];

        return $this->exportService->exportPdf('pharmacy.exports.movements', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
            'filters' => $request->only(['from', 'to']),
        ], 'mouvements');
    }

    /**
     * Export Excel des mouvements de stock.
     */
    public function movementsExcel(Request $request): StreamedResponse
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $isRoot = $header['is_root'];

        $query = StockMovementModel::query()->with('product')->orderByDesc('created_at');
        if (!$isRoot || $shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $movements = $query->get();

        $columns = ['#', 'Date', 'Produit', 'Code', 'Type', 'Quantité', 'Stock avant', 'Stock après', 'Motif'];
        $rows = [];
        $index = 1;

        $typeLabels = [
            'in' => 'Entrée', 'out' => 'Sortie', 'purchase' => 'Achat',
            'sale' => 'Vente', 'return' => 'Retour', 'loss' => 'Perte', 'adjustment' => 'Ajustement'
        ];

        foreach ($movements as $m) {
            $qty = (int) ($m->quantity ?? 0);
            $rows[] = [
                $index++,
                $m->created_at?->format('d/m/Y H:i') ?? '',
                $m->product?->name ?? '',
                $m->product?->code ?? '',
                $typeLabels[$m->type ?? 'adjustment'] ?? $m->type,
                $qty > 0 ? '+' . $qty : $qty,
                (int) ($m->stock_before ?? 0),
                (int) ($m->stock_after ?? 0),
                $m->reason ?? '',
            ];
        }

        return $this->exportService->exportExcel(
            $header,
            'Historique des Mouvements',
            $columns,
            $rows,
            'mouvements',
            ['E' => 'center', 'F' => 'right', 'G' => 'right', 'H' => 'right']
        );
    }
}
