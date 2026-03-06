<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\GcStockMovementModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\Pharmacy\Services\PharmacyExportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GcProductMovementController
{
    public function __construct(
        private readonly PharmacyExportService $exportService
    ) {
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
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

    public function index(Request $request): JsonResponse
    {
        $shopId = $this->getShopId($request);
        $type = $request->input('type');
        $from = $request->input('from');
        $to = $request->input('to');
        $productName = trim((string) $request->input('product_name', ''));
        $productCode = trim((string) $request->input('product_code', ''));

        $q = GcStockMovementModel::query()
            ->where('gc_stock_movements.shop_id', (int) $shopId)
            ->leftJoin('gc_products as p', 'p.id', '=', 'gc_stock_movements.product_id')
            ->leftJoin('gc_categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('users as u', 'u.id', '=', 'gc_stock_movements.created_by')
            ->select([
                'gc_stock_movements.id',
                'gc_stock_movements.product_id',
                'gc_stock_movements.type',
                'gc_stock_movements.quantity',
                'gc_stock_movements.reference',
                'gc_stock_movements.created_at',
                'p.name as product_name',
                'p.sku as product_code',
                'c.name as category_name',
                'u.name as created_by_name',
            ])
            ->orderByDesc('gc_stock_movements.created_at')
            ->limit(1500);

        if ($type && in_array($type, ['IN', 'OUT', 'ADJUSTMENT'], true)) {
            $q->where('gc_stock_movements.type', $type);
        }

        if ($from) {
            $q->whereDate('gc_stock_movements.created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('gc_stock_movements.created_at', '<=', $to);
        }

        if ($productName !== '') {
            $q->where('p.name', 'like', '%' . $productName . '%');
        }
        if ($productCode !== '') {
            $q->where('p.sku', 'like', '%' . $productCode . '%');
        }

        $rows = $q->get();

        $movements = $rows->map(fn ($r) => [
            'id' => (string) $r->id,
            'product_id' => (string) $r->product_id,
            'product_name' => (string) ($r->product_name ?? '—'),
            'product_code' => (string) ($r->product_code ?? ''),
            'category_name' => (string) ($r->category_name ?? ''),
            'type' => (string) $r->type,
            'quantity' => (float) $r->quantity,
            'reference' => $r->reference,
            'created_by_name' => $r->created_by_name ?? null,
            'created_at' => $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i:s') : null,
            'created_at_formatted' => $r->created_at ? Carbon::parse($r->created_at)->format('d/m/Y H:i') : null,
        ])->toArray();

        $stats = [
            'total_movements' => count($movements),
            'total_in' => 0,
            'total_out' => 0,
            'total_adjustment' => 0,
        ];
        foreach ($movements as $m) {
            if ($m['type'] === 'IN') $stats['total_in'] += $m['quantity'];
            elseif ($m['type'] === 'OUT') $stats['total_out'] += $m['quantity'];
            elseif ($m['type'] === 'ADJUSTMENT') $stats['total_adjustment'] += $m['quantity'];
        }

        return response()->json([
            'movements' => $movements,
            'stats' => $stats,
            'filters' => [
                'product_name' => $productName,
                'product_code' => $productCode,
                'type' => $type,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function exportGlobalPdf(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $header = $this->exportService->getExportHeader($request);

        $filters = [
            'product_name' => $request->input('product_name'),
            'product_code' => $request->input('product_code'),
            'type' => $request->input('type'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];
        $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');

        $rows = $this->index($request)->getData(true);
        $movements = $rows['movements'] ?? [];

        // Regrouper par catégorie puis produit (format identique aux templates pharmacy)
        $grouped = [];
        $totals = [
            'total_in' => 0,
            'total_out' => 0,
            'total_adjustment' => 0,
            'total_movements' => count($movements),
        ];

        foreach ($movements as $m) {
            $category = $m['category_name'] ?: 'Sans catégorie';
            $pid = $m['product_id'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'name' => $category,
                    'products' => [],
                    'totals' => ['in' => 0, 'out' => 0, 'adjustment' => 0],
                ];
            }
            if (!isset($grouped[$category]['products'][$pid])) {
                $grouped[$category]['products'][$pid] = [
                    'id' => $pid,
                    'name' => $m['product_name'],
                    'code' => $m['product_code'],
                    'movements' => [],
                    'totals' => ['in' => 0, 'out' => 0, 'adjustment' => 0],
                ];
            }

            $label = match ($m['type']) {
                'IN' => 'Entrée',
                'OUT' => 'Sortie',
                'ADJUSTMENT' => 'Ajustement',
                default => $m['type'],
            };

            $grouped[$category]['products'][$pid]['movements'][] = [
                'created_at' => $m['created_at_formatted'] ?? $m['created_at'] ?? '',
                'reference' => $m['reference'] ?? '',
                'type' => $m['type'],
                'type_label' => $label,
                'quantity' => $m['quantity'],
                'created_by' => $m['created_by_name'] ?? '—',
            ];

            if ($m['type'] === 'IN') {
                $grouped[$category]['totals']['in'] += $m['quantity'];
                $grouped[$category]['products'][$pid]['totals']['in'] += $m['quantity'];
                $totals['total_in'] += $m['quantity'];
            } elseif ($m['type'] === 'OUT') {
                $grouped[$category]['totals']['out'] += $m['quantity'];
                $grouped[$category]['products'][$pid]['totals']['out'] += $m['quantity'];
                $totals['total_out'] += $m['quantity'];
            } else {
                $grouped[$category]['totals']['adjustment'] += $m['quantity'];
                $grouped[$category]['products'][$pid]['totals']['adjustment'] += $m['quantity'];
                $totals['total_adjustment'] += $m['quantity'];
            }
        }

        $categories = array_values(array_map(function ($c) {
            $c['products'] = array_values($c['products']);
            return $c;
        }, $grouped));

        return $this->exportService->exportPdf('commerce.exports.movements-global', [
            'header' => $header,
            'filters' => $filters,
            'categories' => $categories,
            'totals' => $totals,
        ], 'commerce_mouvements', 'landscape');
    }

    public function exportSinglePdf(Request $request, string $id): Response
    {
        $shopId = $this->getShopId($request);
        $header = $this->exportService->getExportHeader($request);

        $movement = GcStockMovementModel::query()
            ->where('shop_id', (int) $shopId)
            ->where('id', $id)
            ->first();
        if (!$movement) {
            abort(404, 'Mouvement introuvable');
        }

        $product = ProductModel::query()->with('category')->find($movement->product_id);
        $creator = $movement->created_by ? UserModel::query()->find($movement->created_by) : null;

        $typeLabel = match ($movement->type) {
            'IN' => 'Entrée',
            'OUT' => 'Sortie',
            'ADJUSTMENT' => 'Ajustement',
            default => $movement->type,
        };

        return $this->exportService->exportPdf('commerce.exports.movement-single', [
            'header' => $header,
            'movement' => [
                'id' => $movement->id,
                'type' => $movement->type,
                'type_label' => $typeLabel,
                'quantity' => $movement->quantity,
                'reference' => $movement->reference,
                'created_at' => $movement->created_at?->format('d/m/Y H:i') ?? '',
                'created_by_name' => $creator?->name ?? '—',
            ],
            'product' => [
                'id' => $product?->id ?? '',
                'name' => $product?->name ?? '—',
                'code' => $product?->sku ?? '',
                'category' => $product?->category?->name ?? '',
                'current_stock' => $product?->stock ?? 0,
                'price' => $product?->sale_price_amount ?? 0,
            ],
        ], 'commerce_mouvement', 'portrait');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $header = $this->exportService->getExportHeader($request);
        $rows = $this->index($request)->getData(true);
        $movements = $rows['movements'] ?? [];

        $columns = ['#', 'Date', 'Produit', 'SKU', 'Catégorie', 'Type', 'Quantité', 'Référence', 'Utilisateur'];
        $data = [];
        $i = 1;
        foreach ($movements as $m) {
            $data[] = [
                $i++,
                $m['created_at_formatted'] ?? $m['created_at'] ?? '',
                $m['product_name'] ?? '',
                $m['product_code'] ?? '',
                $m['category_name'] ?? '',
                $m['type'] ?? '',
                (string) ($m['quantity'] ?? 0),
                $m['reference'] ?? '',
                $m['created_by_name'] ?? '',
            ];
        }

        return $this->exportService->exportExcel(
            $header,
            'Mouvements de stock — Commerce',
            $columns,
            $data,
            'commerce_mouvements',
            ['G' => 'right']
        );
    }
}

