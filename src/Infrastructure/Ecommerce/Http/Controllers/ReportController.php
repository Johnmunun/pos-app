<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\Ecommerce\Models\OrderItemModel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController
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

    /**
     * Devise pour la boutique : shop.currency ou devise par défaut (settings/currencies).
     */
    private function getCurrencyForShop(Request $request, string $shopId): string
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id ?? $shopId;

        $shop = \App\Models\Shop::find($shopId);
        if ($shop && $shop->currency) {
            return $shop->currency;
        }

        $defaultCurrency = \App\Models\Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->first();

        return $defaultCurrency?->code ?? 'CDF';
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        $orders = OrderModel::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->where('payment_status', 'paid')
            ->get();

        $revenue = round($orders->sum('total_amount'), 2);
        $orderCount = $orders->count();

        $chartData = $this->getChartData($shopId, $from, $to);

        $topProducts = OrderItemModel::whereHas('order', fn ($q) => $q->where('shop_id', $shopId)->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to]))
            ->select('product_id', 'product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'product_name' => $r->product_name,
                'quantity' => (float) $r->total_qty,
                'revenue' => round((float) $r->total_revenue, 2),
            ])
            ->toArray();

        $currency = $this->getCurrencyForShop($request, $shopId);

        return Inertia::render('Ecommerce/Reports/Index', [
            'chartData' => $chartData,
            'revenue' => $revenue,
            'orderCount' => $orderCount,
            'topProducts' => $topProducts,
            'currency' => $currency,
            'filters' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
        ]);
    }

    private function getChartData(string $shopId, Carbon $from, Carbon $to): array
    {
        $rows = OrderModel::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as revenue')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $byDate = [];
        foreach ($rows as $r) {
            $d = $r->getAttribute('date');
            $byDate[$d] = ['date' => $d, 'count' => (int) $r->getAttribute('count'), 'revenue' => (float) $r->getAttribute('revenue')];
        }

        $result = [];
        $current = $from->copy();
        while ($current <= $to) {
            $d = $current->format('Y-m-d');
            $result[] = $byDate[$d] ?? ['date' => $d, 'count' => 0, 'revenue' => 0.0];
            $current->addDay();
        }
        return $result;
    }

    public function exportSalesExcel(Request $request): StreamedResponse
    {
        $shopId = $this->getShopId($request);
        $currency = $this->getCurrencyForShop($request, $shopId);

        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        $orders = OrderModel::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ventes');

        $sheet->fromArray(['Date', 'N°', 'Client', 'Total (' . $currency . ')', 'Statut', 'Paiement'], null, 'A1');
        $row = 2;
        foreach ($orders as $o) {
            $sheet->fromArray([
                $o->created_at?->format('d/m/Y H:i'),
                $o->order_number,
                $o->customer_name,
                $o->total_amount,
                $o->status,
                $o->payment_status,
            ], null, 'A' . $row);
            $row++;
        }

        $filename = 'ecommerce_ventes_' . now()->format('Ymd_His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportSalesPdf(Request $request)
    {
        $shopId = $this->getShopId($request);
        $currency = $this->getCurrencyForShop($request, $shopId);

        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        $orders = OrderModel::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $shop = \App\Models\Shop::find($shopId);
        $revenue = $orders->where('payment_status', 'paid')->sum('total_amount');

        $pdf = Pdf::loadView('ecommerce.exports.sales', [
            'orders' => $orders,
            'shop' => $shop,
            'currency' => $currency,
            'from' => $from,
            'to' => $to,
            'revenue' => round($revenue, 2),
        ]);
        return $pdf->download('ecommerce_ventes_' . now()->format('Ymd_His') . '.pdf');
    }
}
