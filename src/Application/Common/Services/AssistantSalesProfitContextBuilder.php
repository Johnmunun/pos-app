<?php

namespace Src\Application\Common\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrégats bénéfice / marge pour les assistants (même logique que les rapports).
 */
final class AssistantSalesProfitContextBuilder
{
    public static function forPharmacy(string $shopId, string $currency = 'CDF'): array
    {
        if (!Schema::hasTable('pharmacy_sales') || !Schema::hasTable('pharmacy_sale_lines')) {
            return [];
        }

        $now = now();

        return [
            'profit_method_note' => 'Bénéfice estimé = CA des ventes terminées − (quantité vendue × prix d\'achat cost_amount du produit). '
                .'Produits sans prix d\'achat : CA compté, coût non déduit.',
            'profit_current_month' => self::aggregatePharmacyPeriod(
                $shopId,
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $currency
            ),
            'profit_last_12_months' => self->pharmacyMonths($shopId, $now->copy()->subMonths(11)->startOfMonth(), $now->copy()->endOfMonth(), $currency),
        ];
    }

    public static function forCommerce(string $shopId, string $currency = 'CDF'): array
    {
        if (!Schema::hasTable('gc_sales') || !Schema::hasTable('gc_sale_lines')) {
            return [];
        }

        $shopIdInt = (int) $shopId;
        $now = now();

        return [
            'profit_method_note' => 'Bénéfice estimé = CA des ventes terminées − (quantité vendue × purchase_price_amount du produit). '
                .'Produits sans prix d\'achat : CA compté, coût non déduit.',
            'profit_current_month' => self::aggregateCommercePeriod(
                $shopIdInt,
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $currency
            ),
            'profit_last_12_months' => self::commerceMonths($shopIdInt, $now->copy()->subMonths(11)->startOfMonth(), $now->copy()->endOfMonth(), $currency),
        ];
    }

    /**
     * Quincaillerie : ventes sur quincaillerie_sales si présent, sinon pharmacy_sales.
     */
    public static function forHardware(string $shopId, string $currency = 'CDF'): array
    {
        if (Schema::hasTable('quincaillerie_sales') && Schema::hasTable('quincaillerie_sale_lines')) {
            $now = now();

            return [
                'profit_method_note' => 'CA des ventes quincaillerie terminées. Les produits quincaillerie n\'ont pas de prix d\'achat en fiche : le bénéfice net n\'est pas calculable, seul le chiffre d\'affaires est fiable.',
                'profit_current_month' => self::aggregateQuincailleriePeriod(
                    $shopId,
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                    $currency
                ),
                'profit_last_12_months' => self::quincaillerieMonths(
                    $shopId,
                    $now->copy()->subMonths(11)->startOfMonth(),
                    $now->copy()->endOfMonth(),
                    $currency
                ),
            ];
        }

        return self::forPharmacy($shopId, $currency);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function pharmacyStockMovements(string $shopId, string $productId, int $limit = 10): array
    {
        if (!Schema::hasTable('pharmacy_stock_movements')) {
            return [];
        }

        $rows = DB::table('pharmacy_stock_movements')
            ->where('shop_id', $shopId)
            ->where('product_id', $productId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['type', 'quantity', 'reference', 'created_at']);

        return $rows->map(fn ($r) => [
            'type' => (string) ($r->type ?? ''),
            'quantity' => (float) ($r->quantity ?? 0),
            'reference' => (string) ($r->reference ?? ''),
            'date' => $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i') : '',
        ])->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function commerceStockMovements(int $shopId, string $productId, int $limit = 10): array
    {
        if (!Schema::hasTable('gc_stock_movements')) {
            return [];
        }

        $rows = DB::table('gc_stock_movements')
            ->where('shop_id', $shopId)
            ->where('product_id', $productId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['type', 'quantity', 'reference', 'created_at']);

        return $rows->map(fn ($r) => [
            'type' => (string) ($r->type ?? ''),
            'quantity' => (float) ($r->quantity ?? 0),
            'reference' => (string) ($r->reference ?? ''),
            'date' => $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i') : '',
        ])->toArray();
    }

    public static function quincaillerieStockMovements(string $shopId, string $productId, int $limit = 10): array
    {
        if (!Schema::hasTable('quincaillerie_stock_movements')) {
            return self::pharmacyStockMovements($shopId, $productId, $limit);
        }

        $rows = DB::table('quincaillerie_stock_movements')
            ->where('shop_id', $shopId)
            ->where('product_id', $productId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['type', 'quantity', 'reference', 'created_at']);

        return $rows->map(fn ($r) => [
            'type' => (string) ($r->type ?? ''),
            'quantity' => (float) ($r->quantity ?? 0),
            'reference' => (string) ($r->reference ?? ''),
            'date' => $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i') : '',
        ])->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private static function aggregatePharmacyPeriod(string $shopId, Carbon $from, Carbon $to, string $currency): array
    {
        $dateExpr = 'COALESCE(s.completed_at, s.created_at)';
        $row = DB::table('pharmacy_sale_lines as sl')
            ->join('pharmacy_sales as s', 'sl.sale_id', '=', 's.id')
            ->leftJoin('pharmacy_products as p', 'sl.product_id', '=', 'p.id')
            ->where('s.shop_id', $shopId)
            ->where('s.status', 'COMPLETED')
            ->whereBetween(DB::raw($dateExpr), [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->selectRaw('COUNT(DISTINCT s.id) as total_sales')
            ->selectRaw('COALESCE(SUM(sl.line_total_amount), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(sl.quantity * COALESCE(p.cost_amount, 0)), 0) as total_cost')
            ->selectRaw('SUM(CASE WHEN p.cost_amount IS NULL THEN 1 ELSE 0 END) as lines_without_cost')
            ->first();

        return self::formatPeriodRow($row, $from, $to, $currency);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function pharmacyMonths(string $shopId, Carbon $from, Carbon $to, string $currency): array
    {
        $dateExpr = 'COALESCE(s.completed_at, s.created_at)';
        $rows = DB::table('pharmacy_sale_lines as sl')
            ->join('pharmacy_sales as s', 'sl.sale_id', '=', 's.id')
            ->leftJoin('pharmacy_products as p', 'sl.product_id', '=', 'p.id')
            ->where('s.shop_id', $shopId)
            ->where('s.status', 'COMPLETED')
            ->whereBetween(DB::raw($dateExpr), [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->groupBy(DB::raw("DATE_FORMAT({$dateExpr}, '%Y-%m')"))
            ->orderBy(DB::raw("DATE_FORMAT({$dateExpr}, '%Y-%m')"))
            ->selectRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') as period")
            ->selectRaw('COUNT(DISTINCT s.id) as total_sales')
            ->selectRaw('COALESCE(SUM(sl.line_total_amount), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(sl.quantity * COALESCE(p.cost_amount, 0)), 0) as total_cost')
            ->get();

        return self::formatMonthRows($rows, $currency);
    }

    /**
     * @return array<string, mixed>
     */
    private static function aggregateCommercePeriod(string $shopId, Carbon $from, Carbon $to, string $currency): array
    {
        $row = DB::table('gc_sale_lines as sl')
            ->join('gc_sales as s', 'sl.sale_id', '=', 's.id')
            ->leftJoin('gc_products as p', 'sl.product_id', '=', 'p.id')
            ->where('s.shop_id', $shopId)
            ->whereRaw('UPPER(s.status) = ?', ['COMPLETED'])
            ->whereBetween('s.created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->selectRaw('COUNT(DISTINCT s.id) as total_sales')
            ->selectRaw('COALESCE(SUM(sl.subtotal), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(sl.quantity * COALESCE(p.purchase_price_amount, 0)), 0) as total_cost')
            ->selectRaw('SUM(CASE WHEN p.purchase_price_amount IS NULL OR p.purchase_price_amount = 0 THEN 1 ELSE 0 END) as lines_without_cost')
            ->first();

        return self::formatPeriodRow($row, $from, $to, $currency);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function commerceMonths(int $shopId, Carbon $from, Carbon $to, string $currency): array
    {
        $rows = DB::table('gc_sale_lines as sl')
            ->join('gc_sales as s', 'sl.sale_id', '=', 's.id')
            ->leftJoin('gc_products as p', 'sl.product_id', '=', 'p.id')
            ->where('s.shop_id', $shopId)
            ->whereRaw('UPPER(s.status) = ?', ['COMPLETED'])
            ->whereBetween('s.created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->groupBy(DB::raw("DATE_FORMAT(s.created_at, '%Y-%m')"))
            ->orderBy(DB::raw("DATE_FORMAT(s.created_at, '%Y-%m')"))
            ->selectRaw("DATE_FORMAT(s.created_at, '%Y-%m') as period")
            ->selectRaw('COUNT(DISTINCT s.id) as total_sales')
            ->selectRaw('COALESCE(SUM(sl.subtotal), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(sl.quantity * COALESCE(p.purchase_price_amount, 0)), 0) as total_cost')
            ->get();

        return self::formatMonthRows($rows, $currency);
    }

    /**
     * @return array<string, mixed>
     */
    private static function aggregateQuincailleriePeriod(string $shopId, Carbon $from, Carbon $to, string $currency): array
    {
        $dateExpr = 'COALESCE(s.completed_at, s.created_at)';
        $row = DB::table('quincaillerie_sale_lines as sl')
            ->join('quincaillerie_sales as s', 'sl.sale_id', '=', 's.id')
            ->where('s.shop_id', $shopId)
            ->where('s.status', 'COMPLETED')
            ->whereBetween(DB::raw($dateExpr), [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->selectRaw('COUNT(DISTINCT s.id) as total_sales')
            ->selectRaw('COALESCE(SUM(sl.line_total_amount), 0) as total_revenue')
            ->first();

        $revenue = (float) ($row->total_revenue ?? 0);
        $fromStr = $from->locale('fr_FR')->isoFormat('MMMM YYYY');

        return [
            'period_label' => $fromStr,
            'period_from' => $from->format('Y-m-d'),
            'period_to' => $to->format('Y-m-d'),
            'total_sales' => (int) ($row->total_sales ?? 0),
            'total_revenue' => $revenue,
            'total_cost' => null,
            'estimated_profit' => null,
            'margin_percent' => null,
            'currency' => $currency,
            'profit_available' => false,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function quincaillerieMonths(string $shopId, Carbon $from, Carbon $to, string $currency): array
    {
        $dateExpr = 'COALESCE(s.completed_at, s.created_at)';
        $rows = DB::table('quincaillerie_sale_lines as sl')
            ->join('quincaillerie_sales as s', 'sl.sale_id', '=', 's.id')
            ->where('s.shop_id', $shopId)
            ->where('s.status', 'COMPLETED')
            ->whereBetween(DB::raw($dateExpr), [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->groupBy(DB::raw("DATE_FORMAT({$dateExpr}, '%Y-%m')"))
            ->orderBy(DB::raw("DATE_FORMAT({$dateExpr}, '%Y-%m')"))
            ->selectRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') as period")
            ->selectRaw('COUNT(DISTINCT s.id) as total_sales')
            ->selectRaw('COALESCE(SUM(sl.line_total_amount), 0) as total_revenue')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $period = (string) ($row->period ?? '');
            $revenue = (float) ($row->total_revenue ?? 0);
            $out[] = [
                'period' => $period,
                'period_label' => $period !== '' ? Carbon::createFromFormat('Y-m', $period)->locale('fr_FR')->isoFormat('MMMM YYYY') : '',
                'total_sales' => (int) ($row->total_sales ?? 0),
                'total_revenue' => $revenue,
                'total_cost' => null,
                'estimated_profit' => null,
                'margin_percent' => null,
                'currency' => $currency,
                'profit_available' => false,
            ];
        }

        return $out;
    }

    /**
     * @param object|null $row
     * @return array<string, mixed>
     */
    private static function formatPeriodRow(?object $row, Carbon $from, Carbon $to, string $currency): array
    {
        $revenue = (float) ($row->total_revenue ?? 0);
        $cost = (float) ($row->total_cost ?? 0);
        $profit = $revenue - $cost;
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : null;

        return [
            'period_label' => $from->locale('fr_FR')->isoFormat('MMMM YYYY'),
            'period_from' => $from->format('Y-m-d'),
            'period_to' => $to->format('Y-m-d'),
            'total_sales' => (int) ($row->total_sales ?? 0),
            'total_revenue' => $revenue,
            'total_cost' => $cost,
            'estimated_profit' => $profit,
            'margin_percent' => $margin,
            'lines_without_cost' => (int) ($row->lines_without_cost ?? 0),
            'currency' => $currency,
            'profit_available' => true,
        ];
    }

    /**
     * @param iterable<object> $rows
     * @return array<int, array<string, mixed>>
     */
    private static function formatMonthRows(iterable $rows, string $currency): array
    {
        $out = [];
        foreach ($rows as $row) {
            $period = (string) ($row->period ?? '');
            $revenue = (float) ($row->total_revenue ?? 0);
            $cost = (float) ($row->total_cost ?? 0);
            $profit = $revenue - $cost;
            $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : null;
            $label = $period !== '' ? Carbon::createFromFormat('Y-m', $period)->locale('fr_FR')->isoFormat('MMMM YYYY') : '';

            $out[] = [
                'period' => $period,
                'period_label' => $label,
                'total_sales' => (int) ($row->total_sales ?? 0),
                'total_revenue' => $revenue,
                'total_cost' => $cost,
                'estimated_profit' => $profit,
                'margin_percent' => $margin,
                'currency' => $currency,
                'profit_available' => true,
            ];
        }

        return $out;
    }

    /**
     * Réponse fallback texte pour une question bénéfice / marge par période.
     */
    public static function tryAnswerProfitQuestion(string $message, array $context, string $currency): ?string
    {
        $lower = mb_strtolower($message);
        if (!preg_match('/bénéfice|benefice|marge|profit|rentabilit/i', $lower)) {
            return null;
        }

        $period = self::resolveProfitPeriodFromQuestion($message, $context);
        if ($period === null && (str_contains($lower, 'mois') || preg_match('/janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|decembre/i', $lower))) {
            return 'Je n\'ai pas de données de bénéfice pour cette période dans les 12 derniers mois chargés. Précisez un autre mois ou demandez le bénéfice du mois en cours.';
        }
        if ($period === null) {
            $period = $context['profit_current_month'] ?? null;
        }
        if ($period === null) {
            return null;
        }

        return self::formatProfitPeriodAnswer($period, $currency);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function resolveProfitPeriodFromQuestion(string $message, array $context): ?array
    {
        $lower = mb_strtolower($message);
        $months = $context['profit_last_12_months'] ?? [];
        $byPeriod = [];
        foreach ($months as $m) {
            $key = (string) ($m['period'] ?? '');
            if ($key !== '') {
                $byPeriod[$key] = $m;
            }
        }

        if (preg_match('/ce mois|mois[- ]?ci|mois en cours|ce mois-ci/u', $lower)) {
            return $context['profit_current_month'] ?? null;
        }

        if (str_contains($lower, 'mois dernier') || str_contains($lower, 'dernier mois')) {
            $key = now()->subMonth()->format('Y-m');

            return $byPeriod[$key] ?? null;
        }

        $year = (int) date('Y');
        if (preg_match('/\b(20\d{2})\b/', $message, $ym)) {
            $year = (int) $ym[1];
        }

        $monthNames = [
            'janvier' => 1, 'février' => 2, 'fevrier' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8, 'aout' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12, 'decembre' => 12,
        ];
        foreach ($monthNames as $name => $num) {
            if (str_contains($lower, $name)) {
                $key = sprintf('%04d-%02d', $year, $num);

                return $byPeriod[$key] ?? null;
            }
        }

        if (preg_match('/\b(\d{4})-(\d{2})\b/', $message, $m)) {
            return $byPeriod[$m[0]] ?? null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $period
     */
    public static function formatProfitPeriodAnswer(array $period, string $currency): string
    {
        $label = (string) ($period['period_label'] ?? $period['period'] ?? 'la période');
        $sales = (int) ($period['total_sales'] ?? 0);
        $revenue = (float) ($period['total_revenue'] ?? 0);
        $curr = (string) ($period['currency'] ?? $currency);

        if (!($period['profit_available'] ?? true)) {
            return sprintf(
                "Pour %s :\n\nVentes terminées : %d\nChiffre d'affaires : %s %s\n\nLe bénéfice net n'est pas calculable : les produits n'ont pas de prix d'achat renseigné en fiche. Renseignez les prix d'achat pour obtenir une marge.",
                $label,
                $sales,
                number_format($revenue, 0, ',', ' '),
                $curr
            );
        }

        $cost = (float) ($period['total_cost'] ?? 0);
        $profit = (float) ($period['estimated_profit'] ?? 0);
        $margin = $period['margin_percent'] ?? null;
        $linesWithout = (int) ($period['lines_without_cost'] ?? 0);

        $body = sprintf(
            "Pour %s :\n\nVentes terminées : %d\nChiffre d'affaires : %s %s\nCoût d'achat estimé (ventes) : %s %s\nBénéfice estimé : %s %s",
            $label,
            $sales,
            number_format($revenue, 0, ',', ' '),
            $curr,
            number_format($cost, 0, ',', ' '),
            $curr,
            number_format($profit, 0, ',', ' '),
            $curr
        );
        if ($margin !== null) {
            $body .= sprintf("\nMarge : %s %%", $margin);
        }
        if ($linesWithout > 0) {
            $body .= sprintf("\n\nNote : %d ligne(s) de vente sans prix d'achat produit (coût non déduit).", $linesWithout);
        }

        return $body;
    }
}
