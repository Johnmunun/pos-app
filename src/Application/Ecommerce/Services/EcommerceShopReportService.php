<?php

namespace Src\Application\Ecommerce\Services;

use App\Models\Shop;
use App\Services\AppNotificationService;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Src\Infrastructure\Ecommerce\Models\EcommerceShopReportModel;

final class EcommerceShopReportService
{
    public function __construct(
        private readonly AppNotificationService $appNotificationService
    ) {
    }

    /**
     * @param array{reason: string, details?: string|null, reporter_name?: string|null, reporter_email?: string|null} $payload
     */
    public function submitReport(Shop $shop, array $payload, ?string $ip, ?string $userAgent): EcommerceShopReportModel
    {
        $reason = (string) ($payload['reason'] ?? '');
        $allowed = array_keys(EcommerceShopReportModel::reasonLabels());
        if (!in_array($reason, $allowed, true)) {
            throw new \InvalidArgumentException('Motif de signalement invalide.');
        }

        if ($ip !== null && $ip !== '') {
            $limit = (int) config('ecommerce.shop_reports.rate_limit_per_shop_ip_per_day', 3);
            $count = EcommerceShopReportModel::query()
                ->where('shop_id', $shop->id)
                ->where('ip_address', $ip)
                ->where('created_at', '>=', now()->subDay())
                ->count();
            if ($count >= $limit) {
                throw new \RuntimeException('Limite de signalements atteinte. Réessayez plus tard.');
            }
        }

        $report = EcommerceShopReportModel::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'shop_id' => $shop->id,
            'tenant_id' => $shop->tenant_id,
            'reason' => $reason,
            'details' => isset($payload['details']) ? trim((string) $payload['details']) : null,
            'reporter_name' => isset($payload['reporter_name']) ? trim((string) $payload['reporter_name']) : null,
            'reporter_email' => isset($payload['reporter_email']) ? trim((string) $payload['reporter_email']) : null,
            'ip_address' => $ip,
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 500) : null,
            'status' => EcommerceShopReportModel::STATUS_PENDING,
        ]);

        $pendingCount = $this->pendingCountForShop((int) $shop->id);
        $warning = (int) config('ecommerce.shop_reports.threshold_warning', 5);
        $critical = (int) config('ecommerce.shop_reports.threshold_critical', 10);

        $level = $pendingCount >= $critical ? 'critique' : ($pendingCount >= $warning ? 'élevé' : 'normal');

        $this->appNotificationService->notifyEcommerceShopReport(
            'Signalement boutique e-commerce',
            sprintf(
                'La boutique « %s » a reçu un signalement (%s). Total en attente : %d (niveau %s).',
                $shop->name,
                EcommerceShopReportModel::reasonLabels()[$reason] ?? $reason,
                $pendingCount,
                $level
            ),
            $shop->tenant_id ? (int) $shop->tenant_id : null
        );

        return $report;
    }

    public function pendingCountForShop(int $shopId): int
    {
        return (int) EcommerceShopReportModel::query()
            ->where('shop_id', $shopId)
            ->where('status', EcommerceShopReportModel::STATUS_PENDING)
            ->count();
    }

    public function suspendShop(Shop $shop, int $adminUserId, string $reason): Shop
    {
        $shop->ecommerce_is_online = false;
        $shop->ecommerce_report_suspended_at = now();
        $shop->ecommerce_report_suspend_reason = trim($reason) !== '' ? trim($reason) : 'Suspendue suite à signalements clients.';
        $shop->ecommerce_report_suspended_by_user_id = $adminUserId;
        $shop->save();

        return $shop->fresh();
    }

    public function restoreShop(Shop $shop): Shop
    {
        $shop->ecommerce_is_online = true;
        $shop->ecommerce_report_suspended_at = null;
        $shop->ecommerce_report_suspend_reason = null;
        $shop->ecommerce_report_suspended_by_user_id = null;
        $shop->save();

        return $shop->fresh();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function shopsWithReportStats(): array
    {
        $warning = (int) config('ecommerce.shop_reports.threshold_warning', 5);
        $critical = (int) config('ecommerce.shop_reports.threshold_critical', 10);

        $aggregates = DB::table('ecommerce_shop_reports')
            ->select([
                'shop_id',
                DB::raw('COUNT(*) as reports_total'),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as reports_pending"),
            ])
            ->groupBy('shop_id')
            ->orderByDesc('reports_pending')
            ->get();

        if ($aggregates->isEmpty()) {
            return [];
        }

        $shopIds = $aggregates->pluck('shop_id')->map(fn ($id) => (int) $id)->all();
        $shops = Shop::query()
            ->with('tenant:id,name')
            ->whereIn('id', $shopIds)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($aggregates as $row) {
            $shop = $shops->get((int) $row->shop_id);
            if ($shop === null) {
                continue;
            }
            $pending = (int) $row->reports_pending;
            $severity = $pending >= $critical ? 'critical' : ($pending >= $warning ? 'warning' : 'ok');

            $result[] = [
                'shop_id' => (int) $shop->id,
                'shop_name' => (string) $shop->name,
                'ecommerce_subdomain' => (string) ($shop->ecommerce_subdomain ?? ''),
                'ecommerce_is_online' => (bool) $shop->ecommerce_is_online,
                'is_suspended' => $shop->ecommerce_report_suspended_at !== null,
                'suspend_reason' => $shop->ecommerce_report_suspend_reason,
                'tenant_name' => (string) ($shop->tenant?->name ?? '—'),
                'reports_total' => (int) $row->reports_total,
                'reports_pending' => $pending,
                'severity' => $severity,
            ];
        }

        return $result;
    }
}
