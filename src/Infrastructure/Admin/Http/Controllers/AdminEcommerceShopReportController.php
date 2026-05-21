<?php

namespace Src\Infrastructure\Admin\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Ecommerce\Services\EcommerceShopReportService;
use Src\Infrastructure\Ecommerce\Models\EcommerceShopReportModel;

final class AdminEcommerceShopReportController
{
    public function __construct(
        private readonly EcommerceShopReportService $reportService
    ) {
    }

    public function index(Request $request): Response
    {
        $shopId = $request->integer('shop_id') ?: null;
        $status = $request->input('status', 'pending');

        $reportsQuery = EcommerceShopReportModel::query()
            ->with(['shop:id,name,ecommerce_subdomain,ecommerce_is_online,ecommerce_report_suspended_at', 'tenant:id,name'])
            ->orderByDesc('created_at');

        if ($shopId) {
            $reportsQuery->where('shop_id', $shopId);
        }
        if ($status !== '' && $status !== 'all') {
            $reportsQuery->where('status', $status);
        }

        $reports = $reportsQuery->limit(200)->get()->map(fn (EcommerceShopReportModel $r) => [
            'id' => $r->id,
            'shop_id' => $r->shop_id,
            'shop_name' => $r->shop?->name ?? '—',
            'ecommerce_subdomain' => $r->shop?->ecommerce_subdomain,
            'tenant_name' => $r->tenant?->name ?? '—',
            'reason' => $r->reason,
            'reason_label' => EcommerceShopReportModel::reasonLabels()[$r->reason] ?? $r->reason,
            'details' => $r->details,
            'reporter_name' => $r->reporter_name,
            'reporter_email' => $r->reporter_email,
            'status' => $r->status,
            'ip_address' => $r->ip_address,
            'created_at' => $r->created_at?->format('d/m/Y H:i'),
            'admin_note' => $r->admin_note,
        ]);

        return Inertia::render('Admin/Ecommerce/ShopReports/Index', [
            'reports' => $reports,
            'shops_summary' => $this->reportService->shopsWithReportStats(),
            'filters' => [
                'shop_id' => $shopId,
                'status' => $status,
            ],
            'reason_labels' => EcommerceShopReportModel::reasonLabels(),
            'thresholds' => [
                'warning' => (int) config('ecommerce.shop_reports.threshold_warning', 5),
                'critical' => (int) config('ecommerce.shop_reports.threshold_critical', 10),
            ],
            'stats' => [
                'pending_total' => (int) EcommerceShopReportModel::query()->where('status', 'pending')->count(),
                'suspended_shops' => (int) Shop::query()->whereNotNull('ecommerce_report_suspended_at')->count(),
            ],
        ]);
    }

    public function dismiss(Request $request, string $id): JsonResponse
    {
        $report = EcommerceShopReportModel::query()->findOrFail($id);
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $report->status = EcommerceShopReportModel::STATUS_DISMISSED;
        $report->reviewed_by_user_id = $request->user()?->id;
        $report->reviewed_at = now();
        $report->admin_note = $validated['admin_note'] ?? null;
        $report->save();

        return response()->json(['success' => true, 'message' => 'Signalement classé sans suite.']);
    }

    public function suspendShop(Request $request, int $shopId): JsonResponse
    {
        $shop = Shop::query()->findOrFail($shopId);
        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Non authentifié.'], 403);
        }

        $this->reportService->suspendShop($shop, (int) $user->id, $validated['reason']);

        EcommerceShopReportModel::query()
            ->where('shop_id', $shopId)
            ->where('status', EcommerceShopReportModel::STATUS_PENDING)
            ->update([
                'status' => EcommerceShopReportModel::STATUS_REVIEWED,
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => now(),
                'admin_note' => 'Boutique suspendue par modération.',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Boutique suspendue. La vitrine publique n\'est plus accessible.',
        ]);
    }

    public function restoreShop(Request $request, int $shopId): JsonResponse
    {
        $shop = Shop::query()->findOrFail($shopId);
        $this->reportService->restoreShop($shop);

        return response()->json([
            'success' => true,
            'message' => 'Boutique réactivée sur la vitrine.',
        ]);
    }
}
