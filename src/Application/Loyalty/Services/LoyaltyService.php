<?php

namespace Src\Application\Loyalty\Services;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Src\Infrastructure\Loyalty\Models\LoyaltyAccountModel;
use Src\Infrastructure\Loyalty\Models\LoyaltySaleLinkModel;
use Src\Infrastructure\Loyalty\Models\LoyaltySettingModel;
use Src\Infrastructure\Loyalty\Models\LoyaltyTransactionModel;

class LoyaltyService
{
    public const MODULE_COMMERCE = 'commerce';
    public const MODULE_PHARMACY = 'pharmacy';
    public const MODULE_HARDWARE = 'hardware';

    public function __construct(
        private readonly LoyaltyLimitService $limitService
    ) {
    }

    public function getSettings(int $tenantId): array
    {
        $row = LoyaltySettingModel::find($tenantId);

        return $this->formatSettings($tenantId, $row);
    }

    public function saveSettings(int $tenantId, array $data): array
    {
        $row = LoyaltySettingModel::find($tenantId);
        if ($row === null) {
            $row = new LoyaltySettingModel(['tenant_id' => $tenantId]);
        }

        $row->fill([
            'enabled' => (bool) ($data['enabled'] ?? false),
            'earn_amount_per_point' => (float) ($data['earn_amount_per_point'] ?? 1),
            'points_per_earn_unit' => max(1, (int) ($data['points_per_earn_unit'] ?? 1)),
            'redeem_value_per_point' => (float) ($data['redeem_value_per_point'] ?? 0.05),
            'min_points_redeem' => max(0, (int) ($data['min_points_redeem'] ?? 100)),
            'max_discount_percent' => min(100, max(0, (float) ($data['max_discount_percent'] ?? 50))),
            'points_expire_days' => isset($data['points_expire_days']) && $data['points_expire_days'] !== ''
                ? (int) $data['points_expire_days']
                : null,
            'tier_thresholds' => $data['tier_thresholds'] ?? $this->defaultTierThresholds(),
            'module_rules' => $data['module_rules'] ?? null,
        ]);
        $row->save();

        return $this->formatSettings($tenantId, $row);
    }

    public function isEnabledForTenant(int $tenantId): bool
    {
        try {
            $this->limitService->assertLoyaltyEnabled((string) $tenantId);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return false;
        }

        $settings = LoyaltySettingModel::find($tenantId);

        return $settings !== null && (bool) $settings->enabled;
    }

    public function resolveAccount(int $tenantId, string $module, string $customerId, bool $autoCreate = true): ?LoyaltyAccountModel
    {
        $account = LoyaltyAccountModel::query()
            ->where('tenant_id', $tenantId)
            ->where('module', $module)
            ->where('customer_id', $customerId)
            ->first();

        if ($account !== null || !$autoCreate) {
            return $account;
        }

        if (!$this->isEnabledForTenant($tenantId)) {
            return null;
        }

        return $this->createAccount($tenantId, $module, $customerId);
    }

    public function createAccount(int $tenantId, string $module, string $customerId): LoyaltyAccountModel
    {
        $this->limitService->assertCanEnrollCustomer((string) $tenantId);

        $existing = LoyaltyAccountModel::query()
            ->where('tenant_id', $tenantId)
            ->where('module', $module)
            ->where('customer_id', $customerId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return LoyaltyAccountModel::create([
            'id' => Uuid::uuid4()->toString(),
            'tenant_id' => $tenantId,
            'module' => $module,
            'customer_id' => $customerId,
            'loyalty_number' => $this->generateUniqueLoyaltyNumber($tenantId),
            'tier' => 'bronze',
            'points_balance' => 0,
            'lifetime_points' => 0,
            'status' => 'active',
        ]);
    }

    public function lookupByCode(int $tenantId, string $code): ?array
    {
        $normalized = strtoupper(trim($code));
        if ($normalized === '') {
            return null;
        }

        $account = LoyaltyAccountModel::query()
            ->where('tenant_id', $tenantId)
            ->where('loyalty_number', $normalized)
            ->where('status', 'active')
            ->first();

        if ($account === null) {
            return null;
        }

        return $this->formatAccountPayload($account);
    }

    public function previewRedemption(int $tenantId, string $module, string $customerId, int $pointsToRedeem, float $saleSubtotal): array
    {
        $settings = $this->requireEnabledSettings($tenantId);
        $account = $this->resolveAccount($tenantId, $module, $customerId, false);
        if ($account === null) {
            throw new \InvalidArgumentException('Ce client n\'a pas de carte fidélité.');
        }

        $discount = $this->computeDiscountAmount($settings, $account, $pointsToRedeem, $saleSubtotal);

        return [
            'points_redeemed' => min($pointsToRedeem, (int) $account->points_balance),
            'discount_amount' => $discount,
            'points_balance' => (int) $account->points_balance,
            'max_redeemable_points' => $this->maxRedeemablePoints($settings, $account, $saleSubtotal),
        ];
    }

    public function computeDiscountAmount(array $settings, LoyaltyAccountModel $account, int $pointsToRedeem, float $saleSubtotal): float
    {
        if ($pointsToRedeem <= 0 || $saleSubtotal <= 0) {
            return 0.0;
        }

        $minRedeem = (int) ($settings['min_points_redeem'] ?? 0);
        if ($pointsToRedeem < $minRedeem) {
            throw new \InvalidArgumentException("Minimum {$minRedeem} points requis pour une réduction.");
        }

        if ($pointsToRedeem > (int) $account->points_balance) {
            throw new \InvalidArgumentException('Solde de points insuffisant.');
        }

        $valuePerPoint = (float) ($settings['redeem_value_per_point'] ?? 0);
        $discount = round($pointsToRedeem * $valuePerPoint, 2);

        $maxPercent = (float) ($settings['max_discount_percent'] ?? 100);
        $maxDiscount = round($saleSubtotal * ($maxPercent / 100), 2);
        $discount = min($discount, $maxDiscount, $saleSubtotal);

        return max(0, $discount);
    }

    public function calculateEarnPoints(array $settings, float $eligibleAmount): int
    {
        if ($eligibleAmount <= 0) {
            return 0;
        }

        $earnUnit = (float) ($settings['earn_amount_per_point'] ?? 1);
        $pointsPerUnit = max(1, (int) ($settings['points_per_earn_unit'] ?? 1));
        if ($earnUnit <= 0) {
            return 0;
        }

        return (int) floor($eligibleAmount / $earnUnit) * $pointsPerUnit;
    }

    /**
     * @return array{points_earned: int, points_redeemed: int, discount_amount: float, account: array}
     */
    public function processCompletedSale(
        int $tenantId,
        string $module,
        string $saleId,
        string $customerId,
        float $saleSubtotal,
        int $pointsToRedeem = 0,
        ?float $fixedDiscountAmount = null,
        ?float $eligibleForEarn = null
    ): array {
        return DB::transaction(function () use ($tenantId, $module, $saleId, $customerId, $saleSubtotal, $pointsToRedeem, $fixedDiscountAmount, $eligibleForEarn) {
            $settings = $this->requireEnabledSettings($tenantId);

            if (LoyaltySaleLinkModel::query()
                ->where('tenant_id', $tenantId)
                ->where('module', $module)
                ->where('sale_id', $saleId)
                ->where('status', 'active')
                ->exists()) {
                throw new \LogicException('Fidélité déjà traitée pour cette vente.');
            }

            $account = LoyaltyAccountModel::query()
                ->where('tenant_id', $tenantId)
                ->where('module', $module)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->first();

            if ($account === null) {
                $account = $this->createAccount($tenantId, $module, $customerId);
                $account = LoyaltyAccountModel::query()->where('id', $account->id)->lockForUpdate()->first();
            }

            $discount = $fixedDiscountAmount !== null
                ? round(max(0, (float) $fixedDiscountAmount), 2)
                : $this->computeDiscountAmount($settings, $account, $pointsToRedeem, $saleSubtotal);
            $eligible = $eligibleForEarn !== null
                ? round(max(0, (float) $eligibleForEarn), 2)
                : round(max(0, $saleSubtotal - $discount), 2);
            $pointsRedeemed = $pointsToRedeem > 0 && $discount > 0 ? $pointsToRedeem : 0;

            if ($pointsRedeemed > 0) {
                $this->applyTransaction($account, 'redeem', -$pointsRedeemed, $module, $saleId, 'Points utilisés sur vente', [
                    'discount_amount' => $discount,
                ]);
                $account->refresh();
            }

            $pointsEarned = $this->calculateEarnPoints($settings, $eligible);
            if ($pointsEarned > 0) {
                $this->applyTransaction($account, 'earn', $pointsEarned, $module, $saleId, 'Points gagnés sur vente', [
                    'eligible_amount' => $eligible,
                ]);
                $account->refresh();
            }

            $account->lifetime_points = (int) $account->lifetime_points + $pointsEarned;
            $account->tier = $this->resolveTier($settings, (int) $account->lifetime_points);
            $account->save();

            LoyaltySaleLinkModel::create([
                'id' => Uuid::uuid4()->toString(),
                'tenant_id' => $tenantId,
                'module' => $module,
                'sale_id' => $saleId,
                'loyalty_account_id' => $account->id,
                'customer_id' => $customerId,
                'points_earned' => $pointsEarned,
                'points_redeemed' => $pointsRedeemed,
                'discount_amount' => $discount,
                'eligible_amount' => $eligible,
                'status' => 'active',
            ]);

            return [
                'points_earned' => $pointsEarned,
                'points_redeemed' => $pointsRedeemed,
                'discount_amount' => $discount,
                'account' => $this->formatAccountPayload($account),
            ];
        });
    }

    public function reverseSale(int $tenantId, string $module, string $saleId): void
    {
        DB::transaction(function () use ($tenantId, $module, $saleId): void {
            $link = LoyaltySaleLinkModel::query()
                ->where('tenant_id', $tenantId)
                ->where('module', $module)
                ->where('sale_id', $saleId)
                ->where('status', 'active')
                ->first();

            if ($link === null) {
                return;
            }

            $account = LoyaltyAccountModel::query()
                ->where('id', $link->loyalty_account_id)
                ->lockForUpdate()
                ->first();

            if ($account === null) {
                $link->status = 'reversed';
                $link->save();

                return;
            }

            if ((int) $link->points_earned > 0) {
                $this->applyTransaction(
                    $account,
                    'reversal',
                    -(int) $link->points_earned,
                    $module,
                    $saleId,
                    'Annulation vente — retrait points gagnés'
                );
                $account->refresh();
            }

            if ((int) $link->points_redeemed > 0) {
                $this->applyTransaction(
                    $account,
                    'reversal',
                    (int) $link->points_redeemed,
                    $module,
                    $saleId,
                    'Annulation vente — remboursement points utilisés'
                );
                $account->refresh();
            }

            $account->lifetime_points = max(0, (int) $account->lifetime_points - (int) $link->points_earned);
            $settings = LoyaltySettingModel::find($tenantId);
            $account->tier = $this->resolveTier(
                $settings ? $this->formatSettings($tenantId, $settings) : $this->formatSettings($tenantId, null),
                (int) $account->lifetime_points
            );
            $account->save();

            $link->status = 'reversed';
            $link->save();
        });
    }

    public function getTransactionHistory(string $accountId, int $limit = 50): array
    {
        return LoyaltyTransactionModel::query()
            ->where('loyalty_account_id', $accountId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'points' => (int) $t->points,
                'balance_after' => (int) $t->balance_after,
                'module' => $t->module,
                'sale_id' => $t->sale_id,
                'description' => $t->description,
                'created_at' => $t->created_at?->toIso8601String(),
            ])
            ->all();
    }

    public function getStats(int $tenantId): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('loyalty_accounts')) {
            return ['accounts' => 0, 'points_issued' => 0, 'points_redeemed' => 0];
        }

        $accounts = (int) DB::table('loyalty_accounts')->where('tenant_id', $tenantId)->where('status', 'active')->count();
        $issued = (int) DB::table('loyalty_transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'earn')
            ->sum('points');
        $redeemed = abs((int) DB::table('loyalty_transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'redeem')
            ->sum('points'));

        $top = LoyaltyAccountModel::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderByDesc('points_balance')
            ->limit(10)
            ->get()
            ->map(fn ($a) => $this->formatTopAccountRow($tenantId, $a))
            ->all();

        return [
            'accounts' => $accounts,
            'points_issued' => $issued,
            'points_redeemed' => $redeemed,
            'top_accounts' => $top,
        ];
    }

    /**
     * Rapport fidélité (période, module optionnel).
     *
     * @return array<string, mixed>
     */
    public function getReport(int $tenantId, ?string $from = null, ?string $to = null, ?string $module = null): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('loyalty_accounts')) {
            return $this->emptyReport($from, $to);
        }

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $accountQuery = LoyaltyAccountModel::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');
        if ($module !== null && $module !== '') {
            $accountQuery->where('module', $module);
        }

        $accountsCount = (int) (clone $accountQuery)->count();

        $txBase = LoyaltyTransactionModel::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$fromDate, $toDate]);
        if ($module !== null && $module !== '') {
            $txBase->where('module', $module);
        }

        $pointsEarnedPeriod = (int) (clone $txBase)->where('type', 'earn')->sum('points');
        $pointsRedeemedPeriod = abs((int) (clone $txBase)->where('type', 'redeem')->sum('points'));
        $pointsExpiredPeriod = abs((int) (clone $txBase)->where('type', 'expire')->sum('points'));
        $pointsReversedPeriod = (int) (clone $txBase)->where('type', 'reversal')->sum('points');

        $linksQuery = LoyaltySaleLinkModel::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereBetween('created_at', [$fromDate, $toDate]);
        if ($module !== null && $module !== '') {
            $linksQuery->where('module', $module);
        }

        $salesWithLoyalty = (int) (clone $linksQuery)->count();
        $discountTotal = round((float) (clone $linksQuery)->sum('discount_amount'), 2);
        $eligibleTotal = round((float) (clone $linksQuery)->sum('eligible_amount'), 2);
        $pointsEarnedOnSales = (int) (clone $linksQuery)->sum('points_earned');
        $pointsRedeemedOnSales = (int) (clone $linksQuery)->sum('points_redeemed');

        $topByBalance = (clone $accountQuery)
            ->orderByDesc('points_balance')
            ->limit(15)
            ->get()
            ->map(fn ($a) => $this->formatTopAccountRow($tenantId, $a))
            ->all();

        $topEarnersPeriod = LoyaltyTransactionModel::query()
            ->select('loyalty_account_id', DB::raw('SUM(points) as earned'))
            ->where('tenant_id', $tenantId)
            ->where('type', 'earn')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->when($module, fn ($q) => $q->where('module', $module))
            ->groupBy('loyalty_account_id')
            ->orderByDesc('earned')
            ->limit(10)
            ->get()
            ->map(function ($row) use ($tenantId) {
                $account = LoyaltyAccountModel::find($row->loyalty_account_id);
                if ($account === null) {
                    return null;
                }

                $formatted = $this->formatTopAccountRow($tenantId, $account);

                return array_merge($formatted, ['points_earned_period' => (int) $row->earned]);
            })
            ->filter()
            ->values()
            ->all();

        $byModule = [];
        foreach ([self::MODULE_COMMERCE, self::MODULE_PHARMACY, self::MODULE_HARDWARE] as $mod) {
            $modAccounts = (int) LoyaltyAccountModel::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('module', $mod)
                ->count();
            if ($modAccounts === 0 && $module !== null && $module !== $mod) {
                continue;
            }
            $modEarned = (int) LoyaltyTransactionModel::query()
                ->where('tenant_id', $tenantId)
                ->where('module', $mod)
                ->where('type', 'earn')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->sum('points');
            $byModule[] = [
                'module' => $mod,
                'accounts' => $modAccounts,
                'points_earned' => $modEarned,
            ];
        }

        $recentActivity = LoyaltyTransactionModel::query()
            ->where('tenant_id', $tenantId)
            ->when($module, fn ($q) => $q->where('module', $module))
            ->orderByDesc('created_at')
            ->limit(25)
            ->get()
            ->map(function ($t) use ($tenantId) {
                $account = LoyaltyAccountModel::find($t->loyalty_account_id);
                $customerName = $account
                    ? $this->resolveCustomerName($tenantId, $account->module, $account->customer_id)
                    : null;

                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'points' => (int) $t->points,
                    'module' => $t->module,
                    'sale_id' => $t->sale_id,
                    'description' => $t->description,
                    'loyalty_number' => $account?->loyalty_number,
                    'customer_name' => $customerName,
                    'created_at' => $t->created_at?->format('d/m/Y H:i'),
                ];
            })
            ->all();

        return [
            'period' => [
                'from' => $fromDate->format('Y-m-d'),
                'to' => $toDate->format('Y-m-d'),
            ],
            'filters' => ['module' => $module],
            'summary' => [
                'accounts' => $accountsCount,
                'points_earned' => $pointsEarnedPeriod,
                'points_redeemed' => $pointsRedeemedPeriod,
                'points_expired' => $pointsExpiredPeriod,
                'points_reversed' => $pointsReversedPeriod,
                'sales_with_loyalty' => $salesWithLoyalty,
                'discount_total' => $discountTotal,
                'eligible_sales_total' => $eligibleTotal,
                'points_earned_on_sales' => $pointsEarnedOnSales,
                'points_redeemed_on_sales' => $pointsRedeemedOnSales,
            ],
            'top_by_balance' => $topByBalance,
            'top_earners_period' => $topEarnersPeriod,
            'by_module' => $byModule,
            'recent_activity' => $recentActivity,
            'loyalty_enabled' => $this->isEnabledForTenant($tenantId),
        ];
    }

    public function resolveCustomerName(int $tenantId, string $module, string $customerId): ?string
    {
        return match ($module) {
            self::MODULE_COMMERCE => Customer::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $customerId)
                ->value('full_name'),
            self::MODULE_PHARMACY, self::MODULE_HARDWARE => \Illuminate\Support\Facades\Schema::hasTable('pharmacy_customers')
                ? DB::table('pharmacy_customers')->where('id', $customerId)->value('name')
                : null,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTopAccountRow(int $tenantId, LoyaltyAccountModel $account): array
    {
        return [
            'id' => $account->id,
            'loyalty_number' => $account->loyalty_number,
            'module' => $account->module,
            'customer_id' => $account->customer_id,
            'customer_name' => $this->resolveCustomerName($tenantId, $account->module, $account->customer_id),
            'points_balance' => (int) $account->points_balance,
            'lifetime_points' => (int) $account->lifetime_points,
            'tier' => $account->tier,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReport(?string $from, ?string $to): array
    {
        $fromDate = $from ? Carbon::parse($from)->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d');
        $toDate = $to ? Carbon::parse($to)->format('Y-m-d') : now()->format('Y-m-d');

        return [
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'filters' => ['module' => null],
            'summary' => [
                'accounts' => 0,
                'points_earned' => 0,
                'points_redeemed' => 0,
                'points_expired' => 0,
                'points_reversed' => 0,
                'sales_with_loyalty' => 0,
                'discount_total' => 0,
                'eligible_sales_total' => 0,
                'points_earned_on_sales' => 0,
                'points_redeemed_on_sales' => 0,
            ],
            'top_by_balance' => [],
            'top_earners_period' => [],
            'by_module' => [],
            'recent_activity' => [],
            'loyalty_enabled' => false,
        ];
    }

    private function applyTransaction(
        LoyaltyAccountModel $account,
        string $type,
        int $pointsDelta,
        ?string $module,
        ?string $saleId,
        string $description,
        array $meta = []
    ): void {
        $newBalance = (int) $account->points_balance + $pointsDelta;
        if ($newBalance < 0) {
            throw new \InvalidArgumentException('Solde de points insuffisant.');
        }

        $account->points_balance = $newBalance;
        $account->save();

        $expiresAt = null;
        $settings = LoyaltySettingModel::find($account->tenant_id);
        if ($type === 'earn' && $settings && $settings->points_expire_days) {
            $expiresAt = now()->addDays((int) $settings->points_expire_days);
        }

        LoyaltyTransactionModel::create([
            'id' => Uuid::uuid4()->toString(),
            'loyalty_account_id' => $account->id,
            'tenant_id' => $account->tenant_id,
            'type' => $type,
            'points' => $pointsDelta,
            'balance_after' => $newBalance,
            'module' => $module,
            'sale_id' => $saleId,
            'description' => $description,
            'meta' => $meta ?: null,
            'expires_at' => $expiresAt,
        ]);
    }

    private function maxRedeemablePoints(array $settings, LoyaltyAccountModel $account, float $saleSubtotal): int
    {
        $balance = (int) $account->points_balance;
        if ($balance <= 0 || $saleSubtotal <= 0) {
            return 0;
        }

        $valuePerPoint = (float) ($settings['redeem_value_per_point'] ?? 0);
        if ($valuePerPoint <= 0) {
            return 0;
        }

        $maxPercent = (float) ($settings['max_discount_percent'] ?? 100);
        $maxDiscount = $saleSubtotal * ($maxPercent / 100);
        $byDiscount = (int) floor($maxDiscount / $valuePerPoint);

        return min($balance, $byDiscount);
    }

    private function requireEnabledSettings(int $tenantId): array
    {
        $this->limitService->assertLoyaltyEnabled((string) $tenantId);
        $settings = $this->getSettings($tenantId);
        if (!($settings['enabled'] ?? false)) {
            throw new \InvalidArgumentException('Le programme de fidélité est désactivé dans les paramètres.');
        }

        return $settings;
    }

    private function generateUniqueLoyaltyNumber(int $tenantId): string
    {
        for ($i = 0; $i < 12; $i++) {
            $candidate = 'FID-' . str_pad((string) $tenantId, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(8));
            $exists = LoyaltyAccountModel::query()
                ->where('tenant_id', $tenantId)
                ->where('loyalty_number', $candidate)
                ->exists();
            if (!$exists) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Impossible de générer un numéro fidélité unique.');
    }

    private function resolveTier(array $settings, int $lifetimePoints): string
    {
        $thresholds = $settings['tier_thresholds'] ?? $this->defaultTierThresholds();
        if ($lifetimePoints >= (int) ($thresholds['vip'] ?? 10000)) {
            return 'vip';
        }
        if ($lifetimePoints >= (int) ($thresholds['gold'] ?? 2000)) {
            return 'gold';
        }
        if ($lifetimePoints >= (int) ($thresholds['silver'] ?? 500)) {
            return 'silver';
        }

        return 'bronze';
    }

    private function defaultTierThresholds(): array
    {
        return ['silver' => 500, 'gold' => 2000, 'vip' => 10000];
    }

    private function formatSettings(int $tenantId, ?LoyaltySettingModel $row): array
    {
        return [
            'tenant_id' => $tenantId,
            'enabled' => (bool) ($row->enabled ?? false),
            'earn_amount_per_point' => (float) ($row->earn_amount_per_point ?? 1),
            'points_per_earn_unit' => (int) ($row->points_per_earn_unit ?? 1),
            'redeem_value_per_point' => (float) ($row->redeem_value_per_point ?? 0.05),
            'min_points_redeem' => (int) ($row->min_points_redeem ?? 100),
            'max_discount_percent' => (float) ($row->max_discount_percent ?? 50),
            'points_expire_days' => $row->points_expire_days ?? null,
            'tier_thresholds' => $row->tier_thresholds ?? $this->defaultTierThresholds(),
            'module_rules' => $row->module_rules ?? null,
        ];
    }

    public function formatAccountPayload(LoyaltyAccountModel $account, ?string $customerName = null): array
    {
        $settings = LoyaltySettingModel::find($account->tenant_id);
        $formattedSettings = $this->formatSettings((int) $account->tenant_id, $settings);

        return [
            'id' => $account->id,
            'tenant_id' => (int) $account->tenant_id,
            'module' => $account->module,
            'customer_id' => $account->customer_id,
            'customer_name' => $customerName,
            'loyalty_number' => $account->loyalty_number,
            'tier' => $account->tier,
            'points_balance' => (int) $account->points_balance,
            'lifetime_points' => (int) $account->lifetime_points,
            'status' => $account->status,
            'redeem_value_per_point' => (float) ($formattedSettings['redeem_value_per_point'] ?? 0),
            'min_points_redeem' => (int) ($formattedSettings['min_points_redeem'] ?? 0),
        ];
    }
}
