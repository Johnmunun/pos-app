<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

class StockController
{
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) abort(403, 'User not authenticated.');
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;
        if (!$shopId && !$isRoot) abort(403, 'Shop ID not found.');
        if ($isRoot && !$shopId) abort(403, 'Please select a shop first.');
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $status = $request->input('status'); // all, low, out

        $query = ProductModel::where('shop_id', $shopId)->where('status', 'active');

        if ($status === 'low') {
            $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'minimum_stock');
        } elseif ($status === 'out') {
            $query->where(function ($q) {
                $q->whereNull('stock')->orWhere('stock', '<=', 0);
            });
        }

        $products = $query->orderByRaw('CASE WHEN stock <= 0 THEN 0 WHEN stock <= minimum_stock THEN 1 ELSE 2 END')
            ->orderBy('stock')
            ->get(['id', 'sku', 'name', 'stock', 'minimum_stock', 'sale_price_amount', 'sale_price_currency'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'stock' => (float) ($p->stock ?? 0),
                'minimum_stock' => (float) ($p->minimum_stock ?? 0),
                'status' => ($p->stock ?? 0) <= 0 ? 'out' : (($p->stock ?? 0) <= ($p->minimum_stock ?? 0) ? 'low' : 'ok'),
                'sale_price' => (float) ($p->sale_price_amount ?? 0),
                'currency' => $p->sale_price_currency ?? 'USD',
            ])
            ->values()
            ->toArray();

        $outOfStock = ProductModel::where('shop_id', $shopId)->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('stock')->orWhere('stock', '<=', 0);
            })->count();

        $lowStock = ProductModel::where('shop_id', $shopId)->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();

        return Inertia::render('Ecommerce/Stock/Index', [
            'products' => $products,
            'out_of_stock_count' => $outOfStock,
            'low_stock_count' => $lowStock,
            'filters' => ['status' => $status ?? 'all'],
        ]);
    }
}
