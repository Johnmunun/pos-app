<?php

/**
 * Smoke test fidélité (CLI) — nécessite une base migrée et un tenant actif.
 *
 * Usage: php scripts/test_loyalty.php [tenant_id]
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Src\Application\Loyalty\Services\LoyaltyService;

$tenantId = (int) ($argv[1] ?? 0);
if ($tenantId <= 0) {
    fwrite(STDERR, "Usage: php scripts/test_loyalty.php <tenant_id>\n");
    exit(1);
}

$service = app(LoyaltyService::class);

echo "=== Loyalty smoke test (tenant {$tenantId}) ===\n";

$settings = $service->getSettings($tenantId);
echo 'Settings enabled: ' . (($settings['enabled'] ?? false) ? 'yes' : 'no') . "\n";

$customerId = 'test-loyalty-' . time();
$account = $service->createAccount($tenantId, LoyaltyService::MODULE_COMMERCE, $customerId);
echo "Account: {$account->loyalty_number} balance={$account->points_balance}\n";

$lookup = $service->lookupByCode($tenantId, $account->loyalty_number);
echo 'Lookup OK: ' . ($lookup !== null ? 'yes' : 'no') . "\n";

$preview = $service->previewRedemption($tenantId, LoyaltyService::MODULE_COMMERCE, $customerId, 0, 100.0);
echo 'Preview 0 pts discount: ' . ($preview['discount_amount'] ?? 0) . "\n";

$saleId = 'test-sale-' . time();
$result = $service->processCompletedSale(
    $tenantId,
    LoyaltyService::MODULE_COMMERCE,
    $saleId,
    $customerId,
    100.0,
    0,
    null,
    100.0
);
echo 'Earned: ' . ($result['points_earned'] ?? 0) . " new balance: " . ($result['points_balance'] ?? '?') . "\n";

$service->reverseSale($tenantId, LoyaltyService::MODULE_COMMERCE, $saleId);
$account->refresh();
echo "After reverse balance: {$account->points_balance}\n";

$stats = $service->getStats($tenantId);
echo 'Stats accounts: ' . ($stats['accounts'] ?? 0) . "\n";

echo "=== Done ===\n";
