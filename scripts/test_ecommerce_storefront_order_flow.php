<?php

/**
 * Test bout-en-bout du parcours commande vitrine e-commerce (CLI).
 *
 * Simule une commande publique (comme le panier vitrine), vérifie :
 * - création commande + déstockage
 * - notification tenant (table notifications)
 * - e-mails (optionnel, avec --send-mail)
 * - dashboard (compteurs commandes / CA)
 * - portefeuille marchand (commande payée à la livraison)
 * - frais de retrait selon le plan billing actif
 *
 * Usage:
 *   php scripts/test_ecommerce_storefront_order_flow.php
 *   php scripts/test_ecommerce_storefront_order_flow.php --subdomain=monshop
 *   php scripts/test_ecommerce_storefront_order_flow.php --shop-id=3
 *   php scripts/test_ecommerce_storefront_order_flow.php --keep
 *   php scripts/test_ecommerce_storefront_order_flow.php --send-mail
 *
 * Par défaut : transaction SQL annulée (--keep pour conserver les données).
 */

use App\Models\AppNotification;
use App\Models\Shop;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Src\Application\Ecommerce\UseCases\UpdatePaymentStatusUseCase;
use Src\Domain\Ecommerce\Entities\Order;
use Src\Infrastructure\Billing\Models\MerchantWalletBalance;
use Src\Infrastructure\Billing\Services\MerchantWalletService;
use Src\Infrastructure\Ecommerce\Http\Controllers\OrderController;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\Ecommerce\Models\PaymentMethodModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel as GcProductModel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$options = getopt('', ['shop-id:', 'subdomain:', 'keep', 'send-mail', 'help']);

if (isset($options['help'])) {
    echo <<<'HELP'
Test parcours commande e-commerce (vitrine)

Options:
  --shop-id=ID       Boutique existante (shops.id)
  --subdomain=NAME   Sous-domaine e-commerce (ecommerce_subdomain)
  --keep             Ne pas annuler la transaction SQL à la fin
  --send-mail        Envoyer de vrais e-mails (sinon Mail::fake())
  --help             Cette aide

HELP;
    exit(0);
}

$keep = array_key_exists('keep', $options);
$sendMail = array_key_exists('send-mail', $options);

function section(string $title): void
{
    echo "\n=== {$title} ===\n";
}

function ok(string $msg): void
{
    echo "  OK: {$msg}\n";
}

function info(string $msg): void
{
    echo "  · {$msg}\n";
}

function fail(string $msg): void
{
    fwrite(STDERR, "  FAIL: {$msg}\n");
    exit(1);
}

function resolveShop(array $options): Shop
{
    if (!empty($options['shop-id'])) {
        $shop = Shop::query()->find((int) $options['shop-id']);
        if ($shop === null) {
            fail('Boutique introuvable pour --shop-id='.$options['shop-id']);
        }

        return $shop;
    }

    if (!empty($options['subdomain'])) {
        $shop = Shop::query()
            ->whereRaw('LOWER(ecommerce_subdomain) = ?', [strtolower((string) $options['subdomain'])])
            ->first();
        if ($shop === null) {
            fail('Boutique introuvable pour sous-domaine: '.$options['subdomain']);
        }

        return $shop;
    }

    $shop = Shop::query()
        ->where('ecommerce_is_online', true)
        ->whereNotNull('ecommerce_subdomain')
        ->where('ecommerce_subdomain', '!=', '')
        ->orderBy('id')
        ->first();

    if ($shop !== null) {
        return $shop;
    }

    $shop = Shop::query()->orderBy('id')->first();
    if ($shop === null) {
        fail('Aucune boutique en base. Créez une boutique ou passez --shop-id / --subdomain.');
    }

    info('Aucune boutique « en ligne » trouvée — utilisation de la première boutique (id='.$shop->id.').');

    return $shop;
}

function ensureActiveSubscription(int $tenantId): void
{
    $exists = DB::table('tenant_plan_subscriptions')
        ->where('tenant_id', $tenantId)
        ->where('status', 'active')
        ->exists();

    if ($exists) {
        return;
    }

    $planId = DB::table('billing_plans')->where('code', 'starter')->value('id')
        ?? DB::table('billing_plans')->orderBy('id')->value('id');

    if ($planId === null) {
        info('Pas de plan billing — limite ventes / portefeuille peuvent être ignorés.');
        return;
    }

    DB::table('tenant_plan_subscriptions')->insert([
        'tenant_id' => $tenantId,
        'billing_plan_id' => $planId,
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => null,
        'trial_ends_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ok('Abonnement actif créé pour les tests (plan id='.$planId.').');
}

function pickProduct(string $shopId, string $preferredMode = 'paiement_livraison'): GcProductModel
{
    $query = GcProductModel::query()
        ->where('shop_id', $shopId)
        ->where('stock', '>', 0)
        ->where('is_active', true);

    if (\Illuminate\Support\Facades\Schema::hasColumn('gc_products', 'is_published_ecommerce')) {
        $query->where('is_published_ecommerce', true);
    }

    $product = (clone $query)
        ->where('mode_paiement', $preferredMode)
        ->first();

    if ($product === null) {
        $product = $query->first();
    }

    if ($product === null) {
        fail('Aucun produit publié avec stock pour shop_id='.$shopId.'. Publiez un produit e-commerce.');
    }

    if ((string) ($product->mode_paiement ?? '') !== $preferredMode) {
        $product->forceFill(['mode_paiement' => $preferredMode])->save();
        info('Produit '.$product->id.' — mode_paiement forcé à '.$preferredMode.' pour le test.');
    }

    return $product;
}

function ensureCodPaymentMethod(Shop $shop): string
{
    $code = 'cod_test_'.$shop->id;

    $existing = PaymentMethodModel::query()
        ->where('shop_id', $shop->id)
        ->where('type', 'cash_on_delivery')
        ->where('is_active', true)
        ->first();

    if ($existing !== null) {
        return (string) $existing->code;
    }

    $byCode = PaymentMethodModel::query()->where('code', $code)->first();
    if ($byCode !== null) {
        $byCode->forceFill([
            'shop_id' => $shop->id,
            'name' => 'Paiement à la livraison (test)',
            'type' => 'cash_on_delivery',
            'is_active' => true,
        ])->save();

        return $code;
    }

    PaymentMethodModel::query()->create([
        'id' => Uuid::uuid4()->toString(),
        'shop_id' => $shop->id,
        'name' => 'Paiement à la livraison (test)',
        'code' => $code,
        'type' => 'cash_on_delivery',
        'is_active' => true,
        'is_default' => false,
        'sort_order' => 99,
    ]);

    return $code;
}

/**
 * @return array{success: bool, data: array<string, mixed>, status: int}
 */
function placeStorefrontOrder(Shop $shop, GcProductModel $product, array $opts = []): array
{
    $qty = (float) ($opts['quantity'] ?? 1);
    $unitPrice = (float) $product->sale_price_amount;
    $subtotal = round($unitPrice * $qty, 2);
    $currency = strtoupper((string) ($opts['currency'] ?? $product->sale_price_currency ?? 'USD'));

    $payload = [
        'customer_name' => $opts['customer_name'] ?? 'Client test CLI',
        'customer_email' => $opts['customer_email'] ?? 'client-test-'.Str::random(6).'@example.test',
        'customer_phone' => '+243900000001',
        'shipping_address' => '12 avenue Test, Kinshasa',
        'billing_address' => null,
        'subtotal_amount' => $subtotal,
        'shipping_amount' => (float) ($opts['shipping_amount'] ?? 0),
        'tax_amount' => (float) ($opts['tax_amount'] ?? 0),
        'discount_amount' => (float) ($opts['discount_amount'] ?? 0),
        'currency' => $currency,
        'payment_method' => $opts['payment_method'] ?? null,
        'payment_status' => $opts['payment_status'] ?? Order::PAYMENT_STATUS_PENDING,
        'notes' => 'Test CLI scripts/test_ecommerce_storefront_order_flow.php',
        'items' => [
            [
                'product_id' => (string) $product->id,
                'product_name' => (string) $product->name,
                'product_sku' => (string) ($product->sku ?? ''),
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'product_image_url' => null,
            ],
        ],
    ];

    $request = Request::create('/orders', 'POST');
    $request->headers->set('Accept', 'application/json');
    $request->merge($payload);
    $request->attributes->set('storefront_shop', $shop);

    $response = app(OrderController::class)->store($request);
    $decoded = json_decode((string) $response->getContent(), true);

    return [
        'success' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 && ($decoded['success'] ?? false),
        'data' => is_array($decoded) ? $decoded : [],
        'status' => $response->getStatusCode(),
    ];
}

function dashboardSnapshot(string $shopId): array
{
    $todayStart = now()->startOfDay();

    return [
        'orders_total' => (int) OrderModel::where('shop_id', $shopId)->count(),
        'orders_today' => (int) OrderModel::where('shop_id', $shopId)->where('created_at', '>=', $todayStart)->count(),
        'revenue_today_paid' => (float) OrderModel::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $todayStart)
            ->sum('total_amount'),
    ];
}

if (!$sendMail) {
    Mail::fake();
}

DB::beginTransaction();

try {
    section('Résolution boutique');
    $shop = resolveShop($options);
    $shopId = (string) $shop->id;
    $tenantId = (int) ($shop->tenant_id ?? 0);

    info('Boutique: '.$shop->name.' (id='.$shopId.', tenant='.$tenantId.')');
    info('Sous-domaine: '.($shop->ecommerce_subdomain ?: '—').' | En ligne: '.($shop->ecommerce_is_online ? 'oui' : 'non'));

    if ($tenantId > 0) {
        ensureActiveSubscription($tenantId);
    }

    if (!$shop->ecommerce_is_online) {
        $shop->forceFill(['ecommerce_is_online' => true])->save();
        info('ecommerce_is_online activé temporairement pour le test.');
    }

    section('Produit & moyen de paiement');
    $product = pickProduct($shopId, 'paiement_livraison');
    $stockBefore = (float) $product->stock;
    $codCode = ensureCodPaymentMethod($shop);
    ok('Produit: '.$product->name.' (id='.$product->id.', stock='.$stockBefore.')');
    ok('Paiement: '.$codCode.' (cash_on_delivery)');

    $dashBefore = dashboardSnapshot($shopId);
    $notifBefore = $tenantId > 0
        ? (int) AppNotification::query()->where('tenant_id', $tenantId)->where('type', 'ecommerce.order.event')->count()
        : 0;

    $walletBefore = $tenantId > 0 && \Illuminate\Support\Facades\Schema::hasTable('merchant_wallet_balances')
        ? (float) (MerchantWalletBalance::query()
            ->where('tenant_id', (string) $tenantId)
            ->where('currency_code', strtoupper((string) ($product->sale_price_currency ?? 'USD')))
            ->value('available_balance') ?? 0)
        : 0.0;

    section('Commande vitrine — paiement à la livraison (payée)');
    $paidResult = placeStorefrontOrder($shop, $product, [
        'payment_method' => $codCode,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
        'customer_email' => 'cli-paid-'.Str::random(5).'@example.test',
    ]);

    if (!$paidResult['success']) {
        fail('Commande payée refusée (HTTP '.$paidResult['status'].'): '.($paidResult['data']['message'] ?? json_encode($paidResult['data'])));
    }

    $orderId = (string) ($paidResult['data']['order']['id'] ?? '');
    $orderNumber = (string) ($paidResult['data']['order']['order_number'] ?? '');
    ok('Commande créée: '.$orderNumber.' (id='.$orderId.')');

    $orderRow = OrderModel::query()->find($orderId);
    if ($orderRow === null || strtolower((string) $orderRow->payment_status) !== 'paid') {
        fail('Statut commande attendu: paid, reçu: '.($orderRow->payment_status ?? 'null'));
    }
    ok('payment_status = paid en base');

    $product->refresh();
    if ((float) $product->stock !== $stockBefore - 1.0) {
        fail('Stock attendu '.($stockBefore - 1).', actuel: '.$product->stock);
    }
    ok('Stock décrémenté: '.$stockBefore.' → '.(float) $product->stock);

    if (!$sendMail) {
        Mail::assertSent(\App\Mail\EcommerceOrderCreatedMail::class);
        ok('E-mail commande créée mis en file (Mail::fake).');
    } else {
        info('E-mails réels envoyés (--send-mail). Vérifiez la boîte configurée.');
    }

    if ($tenantId > 0) {
        $notifAfter = (int) AppNotification::query()
            ->where('tenant_id', $tenantId)
            ->where('type', 'ecommerce.order.event')
            ->count();
        if ($notifAfter <= $notifBefore) {
            fail('Aucune notification ecommerce.order.event créée pour tenant '.$tenantId);
        }
        ok('Notifications vendeur: '.$notifBefore.' → '.$notifAfter);
    }

    $dashAfter = dashboardSnapshot($shopId);
    if ($dashAfter['orders_total'] <= $dashBefore['orders_total']) {
        fail('Dashboard: orders_total inchangé');
    }
    if ($dashAfter['revenue_today_paid'] < $dashBefore['revenue_today_paid'] + (float) $orderRow->total_amount - 0.01) {
        fail('Dashboard: revenue_today_paid non mis à jour');
    }
    ok('Dashboard: commandes '.$dashBefore['orders_total'].'→'.$dashAfter['orders_total']
        .', CA payé aujourd\'hui '.$dashBefore['revenue_today_paid'].'→'.$dashAfter['revenue_today_paid']);

    if ($tenantId > 0 && \Illuminate\Support\Facades\Schema::hasTable('merchant_wallet_balances')) {
        $currency = strtoupper((string) $orderRow->currency);
        $walletAfter = (float) (MerchantWalletBalance::query()
            ->where('tenant_id', (string) $tenantId)
            ->where('currency_code', $currency)
            ->value('available_balance') ?? 0);

        if ($walletAfter <= $walletBefore) {
            fail('Portefeuille: solde disponible non crédité (avant='.$walletBefore.', après='.$walletAfter.')');
        }
        ok('Portefeuille crédité: '.$walletBefore.' → '.$walletAfter.' '.$currency);
    }

    section('Commande vitrine — en attente (pending)');
    $productPending = pickProduct($shopId, 'paiement_immediat');
    $stockPendingBefore = (float) $productPending->stock;

    $pendingResult = placeStorefrontOrder($shop, $productPending, [
        'payment_method' => $codCode,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'customer_email' => 'cli-pending-'.Str::random(5).'@example.test',
    ]);

    if (!$pendingResult['success']) {
        fail('Commande pending refusée: '.($pendingResult['data']['message'] ?? ''));
    }

    $pendingId = (string) ($pendingResult['data']['order']['id'] ?? '');
    $pendingRow = OrderModel::query()->find($pendingId);
    if ($pendingRow === null || strtolower((string) $pendingRow->payment_status) !== 'pending') {
        fail('Commande pending: statut incorrect');
    }
    ok('Commande pending: '.$pendingRow->order_number);

    section('Confirmation paiement manuelle (admin) + portefeuille');
    $walletBeforeManual = $tenantId > 0 && \Illuminate\Support\Facades\Schema::hasTable('merchant_wallet_balances')
        ? (float) (MerchantWalletBalance::query()
            ->where('tenant_id', (string) $tenantId)
            ->where('currency_code', strtoupper((string) $pendingRow->currency))
            ->value('available_balance') ?? 0)
        : 0.0;

    app(UpdatePaymentStatusUseCase::class)->execute($pendingId, Order::PAYMENT_STATUS_PAID);
    $pendingRow->refresh();

    if (strtolower((string) $pendingRow->payment_status) !== 'paid') {
        fail('Après updatePaymentStatus: statut non paid');
    }
    ok('Commande '.$pendingRow->order_number.' marquée payée');

    if ($tenantId > 0 && \Illuminate\Support\Facades\Schema::hasTable('merchant_wallet_balances')) {
        $walletAfterManual = (float) (MerchantWalletBalance::query()
            ->where('tenant_id', (string) $tenantId)
            ->where('currency_code', strtoupper((string) $pendingRow->currency))
            ->value('available_balance') ?? 0);

        if ($walletAfterManual <= $walletBeforeManual) {
            fail('Portefeuille non crédité après confirmation manuelle');
        }
        ok('Portefeuille après confirmation: '.$walletBeforeManual.' → '.$walletAfterManual);
    }

    section('Frais de retrait (plan root / billing_plans)');
    if ($tenantId > 0) {
        $walletService = app(MerchantWalletService::class);
        $feePercent = $walletService->getWithdrawalFeePercentForTenant((string) $tenantId);
        $estimate = $walletService->estimateWithdrawalNet((string) $tenantId, 100);
        info('withdrawal_fee_percent actif: '.$feePercent.'%');
        info('Estimation sur 100: frais='.$estimate['fee_amount'].', net='.$estimate['net_amount']);

        $available = (float) (MerchantWalletBalance::query()
            ->where('tenant_id', (string) $tenantId)
            ->orderByDesc('available_balance')
            ->value('available_balance') ?? 0);

        if ($available >= 10) {
            $amount = min(10.0, $available);
            $user = \App\Models\User::query()->where('tenant_id', $tenantId)->first();
            if ($user !== null) {
                $withdrawal = $walletService->createWithdrawalRequest($user, [
                    'currency_code' => strtoupper((string) ($pendingRow->currency ?? 'USD')),
                    'requested_amount' => $amount,
                    'destination_type' => 'mobile_money',
                    'destination_reference' => 'TEST-CLI-'.Str::random(4),
                ]);
                ok('Demande retrait test: '.$amount.' → net '.(float) $withdrawal->net_amount
                    .' (frais '.(float) $withdrawal->fee_amount.', statut '.$withdrawal->status.')');
            } else {
                info('Pas d\'utilisateur tenant — retrait non simulé.');
            }
        } else {
            info('Solde disponible insuffisant pour simuler un retrait (disponible='.$available.').');
        }
    }

    if ($keep) {
        DB::commit();
        echo "\nTous les tests sont passés. Données CONSERVÉES (--keep).\n";
        echo "Commandes créées: {$orderNumber}, {$pendingRow->order_number}\n";
    } else {
        DB::rollBack();
        echo "\nTous les tests sont passés. Transaction SQL annulée (aucune donnée persistante).\n";
        echo "Relancez avec --keep pour garder les commandes de test en base.\n";
    }
} catch (\Throwable $e) {
    DB::rollBack();
    fail($e->getMessage()."\n".$e->getTraceAsString());
}
