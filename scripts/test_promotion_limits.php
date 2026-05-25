<?php

/**
 * Vérifie le comptage et les quotas promotions (CLI).
 *
 * Usage: php scripts/test_promotion_limits.php [--tenant-id=1]
 */

use Src\Application\Billing\Services\PromotionLimitService;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenantId = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--tenant-id=')) {
        $tenantId = substr($arg, strlen('--tenant-id='));
    }
}

if ($tenantId === null || $tenantId === '') {
    $tenantId = (string) (\Illuminate\Support\Facades\DB::table('tenant_plan_subscriptions')
        ->where('status', 'active')
        ->orderByDesc('id')
        ->value('tenant_id') ?? '');
}

if ($tenantId === '') {
    fwrite(STDERR, "Aucun tenant actif. Passez --tenant-id=ID\n");
    exit(1);
}

/** @var PromotionLimitService $service */
$service = app(PromotionLimitService::class);

$summary = $service->getQuotaSummary($tenantId);
$products = $service->countActivePromotionalProducts($tenantId);
$campaigns = $service->countActiveEcommercePromotions($tenantId);

echo "Tenant: {$tenantId}\n";
echo "Produits en promotion (actifs): {$products}\n";
echo "Campagnes e-commerce actives: {$campaigns}\n";
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\nOK — PromotionLimitService opérationnel.\n";
