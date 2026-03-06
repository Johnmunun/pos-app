<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\PurchaseModel;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\SupplierModel;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleModel;
use Src\Infrastructure\Pharmacy\Services\PharmacyExportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GcExportController
{
    public function __construct(
        private readonly PharmacyExportService $exportService
    ) {
    }
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

    private function getTenantId(Request $request): int
    {
        $user = $request->user();
        if ($user === null || !$user->tenant_id) {
            abort(403, 'Tenant not found.');
        }

        return (int) $user->tenant_id;
    }

    private function streamSpreadsheet(Spreadsheet $spreadsheet, string $baseName): StreamedResponse
    {
        $filename = $baseName . '_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function productsExcel(Request $request): StreamedResponse
    {
        $shopId = $this->getShopId($request);
        $products = ProductModel::byShop($shopId)
            ->with('category')
            ->orderBy('name')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Produits');

        $headers = [
            '#',
            'SKU',
            'Nom',
            'Catégorie',
            'Prix achat',
            'Prix vente',
            'Stock',
            'Stock min.',
            'Actif',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $index = 1;
        foreach ($products as $product) {
            $sheet->fromArray([
                $index++,
                $product->sku,
                $product->name,
                $product->category?->name ?? '',
                $product->purchase_price_amount,
                $product->sale_price_amount,
                $product->stock,
                $product->minimum_stock,
                $product->is_active ? 'Oui' : 'Non',
            ], null, 'A' . $row);
            $row++;
        }

        return $this->streamSpreadsheet($spreadsheet, 'commerce_produits');
    }

    public function categoriesExcel(Request $request): StreamedResponse
    {
        $shopId = $this->getShopId($request);
        $categories = CategoryModel::byShop($shopId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Catégories');

        $headers = [
            '#',
            'Nom',
            'Description',
            'Parent',
            'Ordre',
            'Actif',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $index = 1;
        $parents = CategoryModel::byShop($shopId)->get(['id', 'name'])->keyBy('id');

        foreach ($categories as $category) {
            $sheet->fromArray([
                $index++,
                $category->name,
                $category->description,
                $category->parent_id ? ($parents[$category->parent_id]->name ?? '') : '',
                $category->sort_order,
                $category->is_active ? 'Oui' : 'Non',
            ], null, 'A' . $row);
            $row++;
        }

        return $this->streamSpreadsheet($spreadsheet, 'commerce_categories');
    }

    public function suppliersExcel(Request $request): StreamedResponse
    {
        $shopId = $this->getShopId($request);
        $suppliers = SupplierModel::byShop($shopId)
            ->orderBy('name')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Fournisseurs');

        $headers = [
            '#',
            'Nom',
            'Email',
            'Téléphone',
            'Adresse',
            'Actif',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $index = 1;
        foreach ($suppliers as $supplier) {
            $sheet->fromArray([
                $index++,
                $supplier->name,
                $supplier->email,
                $supplier->phone,
                $supplier->address,
                $supplier->is_active ? 'Oui' : 'Non',
            ], null, 'A' . $row);
            $row++;
        }

        return $this->streamSpreadsheet($spreadsheet, 'commerce_fournisseurs');
    }

    public function customersExcel(Request $request): StreamedResponse
    {
        $tenantId = $this->getTenantId($request);
        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('full_name')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Clients');

        $headers = [
            '#',
            'Code',
            'Nom complet',
            'Email',
            'Téléphone',
            'Adresse',
            'Actif',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $index = 1;
        foreach ($customers as $customer) {
            $sheet->fromArray([
                $index++,
                $customer->code,
                $customer->full_name,
                $customer->email,
                $customer->phone,
                $customer->address,
                $customer->is_active ? 'Oui' : 'Non',
            ], null, 'A' . $row);
            $row++;
        }

        return $this->streamSpreadsheet($spreadsheet, 'commerce_clients');
    }

    public function salesExcel(Request $request): StreamedResponse
    {
        $shopId = $this->getShopId($request);
        $sales = SaleModel::query()
            ->where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ventes');

        $headers = [
            '#',
            'Réf.',
            'Date',
            'Client',
            'Montant',
            'Devise',
            'Statut',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $index = 1;
        foreach ($sales as $sale) {
            $sheet->fromArray([
                $index++,
                $sale->id,
                optional($sale->created_at)->format('d/m/Y H:i'),
                $sale->customer_name,
                $sale->total_amount,
                $sale->currency,
                $sale->status,
            ], null, 'A' . $row);
            $row++;
        }

        return $this->streamSpreadsheet($spreadsheet, 'commerce_ventes');
    }

    public function purchasesExcel(Request $request): StreamedResponse
    {
        $shopId = $this->getShopId($request);
        $purchases = PurchaseModel::query()
            ->where('shop_id', $shopId)
            ->with('supplier')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Achats');

        $headers = [
            '#',
            'Réf.',
            'Date',
            'Fournisseur',
            'Montant',
            'Devise',
            'Statut',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $index = 1;
        foreach ($purchases as $purchase) {
            $sheet->fromArray([
                $index++,
                $purchase->id,
                optional($purchase->created_at)->format('d/m/Y H:i'),
                $purchase->supplier?->name ?? '',
                $purchase->total_amount,
                $purchase->currency,
                $purchase->status,
            ], null, 'A' . $row);
            $row++;
        }

        return $this->streamSpreadsheet($spreadsheet, 'commerce_achats');
    }

    /**
     * Export PDF du stock Global Commerce.
     */
    public function stockPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $currency = $header['currency'];

        $products = ProductModel::byShop($shopId)
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $items = $products->map(function (ProductModel $p) {
            $stock = (float) ($p->stock ?? 0);
            $min = (float) ($p->minimum_stock ?? 0);
            return [
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category?->name ?? '',
                'stock' => $stock,
                'minimum_stock' => $min,
                'purchase_price' => (float) $p->purchase_price_amount,
                'sale_price' => (float) $p->sale_price_amount,
                'stock_value' => $stock * (float) $p->sale_price_amount,
            ];
        })->toArray();

        $summary = [
            'total_products' => count($items),
            'low_stock' => collect($items)->filter(fn ($i) => $i['stock'] > 0 && $i['stock'] <= $i['minimum_stock'])->count(),
            'out_of_stock' => collect($items)->filter(fn ($i) => $i['stock'] <= 0)->count(),
            'total_value' => collect($items)->sum('stock_value'),
            'currency' => $currency,
        ];

        return $this->exportService->exportPdf('commerce.exports.stock', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'commerce_stock', 'landscape');
    }

    /**
     * Export Excel du stock Global Commerce.
     */
    public function stockExcel(Request $request): StreamedResponse
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];
        $currency = $header['currency'];

        $products = ProductModel::byShop($shopId)
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $columns = ['#', 'SKU', 'Produit', 'Catégorie', 'Stock', 'Seuil min.', 'Prix vente', 'Valeur stock'];
        $rows = [];
        $index = 1;

        foreach ($products as $p) {
            $stock = (float) ($p->stock ?? 0);
            $stockValue = $stock * (float) $p->sale_price_amount;
            $rows[] = [
                $index++,
                $p->sku,
                $p->name,
                $p->category?->name ?? '',
                $stock,
                (float) ($p->minimum_stock ?? 0),
                number_format((float) $p->sale_price_amount, 2) . ' ' . $currency,
                number_format($stockValue, 2) . ' ' . $currency,
            ];
        }

        return $this->exportService->exportExcel(
            $header,
            'Stock Global Commerce',
            $columns,
            $rows,
            'commerce_stock',
            ['E' => 'right', 'F' => 'right', 'G' => 'right', 'H' => 'right']
        );
    }

    /**
     * Exports PDF
     */

    public function productsPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];

        $products = ProductModel::byShop($shopId)
            ->with('category')
            ->orderBy('name')
            ->get();

        $items = [];
        $index = 1;
        foreach ($products as $p) {
            $items[] = [
                'index' => $index++,
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category?->name ?? '',
                'purchase_price' => (float) $p->purchase_price_amount,
                'sale_price' => (float) $p->sale_price_amount,
                'stock' => (float) $p->stock,
                'minimum_stock' => (float) $p->minimum_stock,
                'is_active' => (bool) $p->is_active,
            ];
        }

        $summary = [
            'total' => count($items),
            'active' => collect($items)->filter(fn ($i) => $i['is_active'])->count(),
            'inactive' => collect($items)->filter(fn ($i) => !$i['is_active'])->count(),
        ];

        return $this->exportService->exportPdf('commerce.exports.products', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'commerce_produits');
    }

    public function categoriesPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $header['shop_id'];

        $categories = CategoryModel::byShop($shopId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $parents = $categories->keyBy('id');
        $items = [];
        $index = 1;
        foreach ($categories as $c) {
            $items[] = [
                'index' => $index++,
                'name' => $c->name,
                'description' => $c->description,
                'parent' => $c->parent_id ? ($parents[$c->parent_id]->name ?? '') : '',
                'sort_order' => $c->sort_order,
                'is_active' => (bool) $c->is_active,
            ];
        }

        $summary = [
            'total' => count($items),
            'active' => collect($items)->filter(fn ($i) => $i['is_active'])->count(),
            'inactive' => collect($items)->filter(fn ($i) => !$i['is_active'])->count(),
        ];

        return $this->exportService->exportPdf('commerce.exports.categories', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'commerce_categories');
    }

    public function suppliersPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $this->getShopId($request);

        $suppliers = SupplierModel::byShop($shopId)
            ->orderBy('name')
            ->get();

        $items = [];
        $index = 1;
        foreach ($suppliers as $s) {
            $items[] = [
                'index' => $index++,
                'name' => $s->name,
                'email' => $s->email,
                'phone' => $s->phone,
                'address' => $s->address,
                'is_active' => (bool) $s->is_active,
            ];
        }

        $summary = [
            'total' => count($items),
            'active' => collect($items)->filter(fn ($i) => $i['is_active'])->count(),
            'inactive' => collect($items)->filter(fn ($i) => !$i['is_active'])->count(),
        ];

        return $this->exportService->exportPdf('commerce.exports.suppliers', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'commerce_fournisseurs');
    }

    public function customersPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $tenantId = $this->getTenantId($request);

        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('full_name')
            ->get();

        $items = [];
        $index = 1;
        foreach ($customers as $c) {
            $items[] = [
                'index' => $index++,
                'code' => $c->code,
                'name' => $c->full_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'address' => $c->address,
                'is_active' => (bool) $c->is_active,
            ];
        }

        $summary = [
            'total' => count($items),
            'active' => collect($items)->filter(fn ($i) => $i['is_active'])->count(),
            'inactive' => collect($items)->filter(fn ($i) => !$i['is_active'])->count(),
        ];

        return $this->exportService->exportPdf('commerce.exports.customers', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'commerce_clients');
    }

    public function salesPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $this->getShopId($request);

        $sales = SaleModel::query()
            ->where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $items = [];
        $index = 1;
        foreach ($sales as $s) {
            $items[] = [
                'index' => $index++,
                'id' => $s->id,
                'date' => optional($s->created_at)->format('d/m/Y H:i'),
                'customer_name' => $s->customer_name,
                'total_amount' => (float) $s->total_amount,
                'currency' => $s->currency,
                'status' => $s->status,
            ];
        }

        $summary = [
            'total' => count($items),
            'total_amount' => collect($items)->sum('total_amount'),
        ];

        return $this->exportService->exportPdf('commerce.exports.sales', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'commerce_ventes');
    }

    public function purchasesPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $this->getShopId($request);

        $purchases = PurchaseModel::query()
            ->where('shop_id', $shopId)
            ->with('supplier')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $items = [];
        $index = 1;
        foreach ($purchases as $p) {
            $items[] = [
                'index' => $index++,
                'id' => $p->id,
                'date' => optional($p->created_at)->format('d/m/Y H:i'),
                'supplier_name' => $p->supplier?->name ?? '',
                'total_amount' => (float) $p->total_amount,
                'currency' => $p->currency,
                'status' => $p->status,
            ];
        }

        $summary = [
            'total' => count($items),
            'total_amount' => collect($items)->sum('total_amount'),
        ];

        return $this->exportService->exportPdf('commerce.exports.purchases', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'commerce_achats');
    }

    /**
     * Export PDF du rapport d'activité global (période from/to).
     */
    public function reportPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $report = app(GcReportController::class)->getReportData($request);

        return $this->exportService->exportPdf('commerce.exports.report', [
            'header' => $header,
            'report' => $report,
            'filters' => $request->only(['from', 'to']),
        ], 'rapport-activite-commerce', 'portrait');
    }

    /**
     * Export Excel du rapport d'activité global (période from/to).
     */
    public function reportExcel(Request $request): StreamedResponse
    {
        $header = $this->exportService->getExportHeader($request);
        $currency = $header['currency'] ?? 'CDF';
        $report = app(GcReportController::class)->getReportData($request);

        $from = $report['period']['from'] ?? $request->input('from', '');
        $to = $report['period']['to'] ?? $request->input('to', '');
        $sales = $report['sales'] ?? [];
        $purchases = $report['purchases'] ?? [];
        $movements = $report['movements'] ?? [];
        $stock = $report['stock'] ?? [];

        $columns = ['Indicateur', 'Valeur'];
        $rows = [
            ['Période', $from . ' → ' . $to],
            ['Chiffre d\'affaires', number_format($sales['total'] ?? 0, 2, ',', ' ') . ' ' . $currency],
            ['Nombre de ventes', $sales['count'] ?? 0],
            ['Achats reçus (nombre)', $purchases['count'] ?? 0],
            ['Achats reçus (montant)', number_format($purchases['total'] ?? 0, 2, ',', ' ') . ' ' . $currency],
            ['Mouvements de stock (total opérations)', $movements['total_ops'] ?? 0],
            ['Entrées stock', $movements['qty_in'] ?? 0],
            ['Sorties stock', $movements['qty_out'] ?? 0],
            ['Produits actifs', $stock['product_count'] ?? 0],
            ['Valeur du stock', number_format($stock['total_value'] ?? 0, 2, ',', ' ') . ' ' . $currency],
            ['Produits en stock bas', $stock['low_stock_count'] ?? 0],
        ];

        return $this->exportService->exportExcel(
            $header,
            'Rapport d\'activité Commerce',
            $columns,
            $rows,
            'rapport-activite-commerce',
            ['B' => 'right']
        );
    }
}

