<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

class StorefrontAiSupportController
{
    public function __construct(
        private readonly FeatureLimitService $featureLimitService,
    ) {
    }

    public function ask(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);
        if (!$shop) {
            return response()->json(['message' => 'Boutique introuvable.'], 404);
        }

        $tenantId = $shop->tenant_id !== null ? (string) $shop->tenant_id : (string) $shop->id;
        $this->featureLimitService->assertFeatureEnabled($tenantId, 'ai.ecommerce.support');
        $this->featureLimitService->assertCanUseMonthlyFeature($tenantId, 'ai.ecommerce.support', 'le support client IA e-commerce');

        $cfg = $shop->ecommerce_storefront_config;
        if (!is_array($cfg)) {
            $cfg = [];
        }
        if (!(bool) ($cfg['ai_support_enabled'] ?? false)) {
            return response()->json(['message' => 'Le support IA est désactivé par la boutique.'], 403);
        }

        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'order_number' => 'nullable|string|max:80',
            'customer_email' => 'nullable|email|max:255',
        ]);

        $message = trim((string) $data['message']);
        $topic = $this->detectTopic($message);

        $context = [
            'shop_name' => (string) $shop->name,
            'shipping_policy' => (string) ($cfg['ai_support_shipping_policy'] ?? ''),
            'returns_policy' => (string) ($cfg['ai_support_returns_policy'] ?? ''),
            'flat_shipping_enabled' => (bool) ($cfg['storefront_use_flat_shipping'] ?? false),
            'flat_shipping_amount' => isset($cfg['storefront_flat_shipping_amount']) ? (float) $cfg['storefront_flat_shipping_amount'] : 0.0,
        ];

        if ($topic === 'availability') {
            $context['availability'] = $this->findAvailability($shop, $message);
        }
        if ($topic === 'order_status') {
            $context['order_status'] = $this->findOrderStatus(
                $shop,
                (string) ($data['order_number'] ?? ''),
                (string) ($data['customer_email'] ?? ''),
                $message
            );
        }

        $answer = $this->generateAnswerWithFallback($message, $topic, $context, (string) ($cfg['ai_support_tone'] ?? 'friendly'));
        $this->featureLimitService->recordFeatureUsage($tenantId, 'ai.ecommerce.support');

        return response()->json(['answer' => $answer]);
    }

    private function resolveShop(Request $request): ?Shop
    {
        $publicShop = $request->attributes->get('storefront_shop');
        if ($publicShop instanceof Shop) {
            return $publicShop;
        }

        $user = $request->user();
        if (!$user) {
            return null;
        }

        $shopId = $user->shop_id ?? null;
        if (($shopId === null || $shopId === '') && $user->tenant_id) {
            $shop = Shop::where('tenant_id', $user->tenant_id)->first();
            if ($shop) {
                return $shop;
            }
        }

        if (($shopId === null || $shopId === '') && method_exists($user, 'isRoot') && $user->isRoot()) {
            $shopId = $request->session()->get('current_storefront_shop_id');
        }

        return $shopId ? Shop::find((int) $shopId) : null;
    }

    private function detectTopic(string $message): string
    {
        $m = mb_strtolower($message);
        if (str_contains($m, 'retour') || str_contains($m, 'rembourse')) {
            return 'returns';
        }
        if (str_contains($m, 'livraison') || str_contains($m, 'expédition') || str_contains($m, 'delai')) {
            return 'shipping';
        }
        if (str_contains($m, 'commande') || str_contains($m, 'statut') || str_contains($m, 'order')) {
            return 'order_status';
        }

        return 'availability';
    }

    /** @return array<string,mixed> */
    private function findAvailability(Shop $shop, string $message): array
    {
        $term = trim($message);
        if ($term === '' || !Schema::hasTable('gc_products')) {
            return ['found' => false];
        }

        $q = ProductModel::query()
            ->where('shop_id', $shop->id)
            ->where(function ($sub) use ($term): void {
                $sub->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%');
            })
            ->orderByDesc('stock')
            ->first(['name', 'stock', 'status']);

        if (!$q) {
            return ['found' => false];
        }

        return [
            'found' => true,
            'name' => (string) $q->name,
            'stock' => (float) ($q->stock ?? 0),
            'status' => (string) ($q->status ?? ''),
        ];
    }

    /** @return array<string,mixed> */
    private function findOrderStatus(Shop $shop, string $orderNumber, string $email, string $message): array
    {
        $orderRef = trim($orderNumber);
        if ($orderRef === '' && preg_match('/([A-Z0-9-]{6,})/i', $message, $m)) {
            $orderRef = trim((string) $m[1]);
        }
        if ($orderRef === '') {
            return ['found' => false, 'reason' => 'missing_reference'];
        }

        $query = OrderModel::query()->where('shop_id', (string) $shop->id)->where('order_number', $orderRef);
        if ($email !== '') {
            $query->where('customer_email', $email);
        }
        $order = $query->first(['order_number', 'status', 'payment_status', 'customer_email']);
        if (!$order) {
            return ['found' => false, 'reason' => 'not_found'];
        }

        return [
            'found' => true,
            'order_number' => (string) $order->order_number,
            'status' => (string) $order->status,
            'payment_status' => (string) ($order->payment_status ?? 'pending'),
        ];
    }

    /** @param array<string,mixed> $context */
    private function generateAnswerWithFallback(string $message, string $topic, array $context, string $tone): string
    {
        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey !== '') {
            try {
                $system = "Tu es assistant support e-commerce. Réponds en français, court (2-5 phrases), concret, ton {$tone}.";
                $user = "Contexte JSON:\n".json_encode($context, JSON_UNESCAPED_UNICODE)."\n\nTopic: {$topic}\nQuestion: {$message}";
                $res = Http::withToken($apiKey)->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 280,
                ]);
                if ($res->successful()) {
                    $content = trim((string) ($res->json('choices.0.message.content') ?? ''));
                    if ($content !== '') {
                        return $content;
                    }
                }
            } catch (\Throwable) {
                // fallback below
            }
        }

        if ($topic === 'shipping') {
            if (($context['flat_shipping_enabled'] ?? false) === true) {
                $amount = number_format((float) ($context['flat_shipping_amount'] ?? 0), 2, ',', ' ');
                return "La boutique applique actuellement des frais de livraison fixes de {$amount}. "
                    ."Les délais précis dépendent de votre zone. "
                    ."N'hésitez pas à préciser votre ville pour une estimation plus fine.";
            }
            return (string) ($context['shipping_policy'] ?: 'La livraison dépend de votre zone et du transporteur. Donnez votre localisation pour une estimation.');
        }
        if ($topic === 'returns') {
            return (string) ($context['returns_policy'] ?: 'Les retours sont étudiés au cas par cas selon l’état du produit et les délais légaux de la boutique.');
        }
        if ($topic === 'order_status') {
            $os = $context['order_status'] ?? ['found' => false];
            if (($os['found'] ?? false) === true) {
                return "Commande {$os['order_number']} : statut {$os['status']}, paiement {$os['payment_status']}.";
            }
            if (($os['reason'] ?? '') === 'missing_reference') {
                return 'Pour vérifier le statut de votre commande, indiquez votre numéro de commande (et idéalement votre email).';
            }
            return 'Je ne retrouve pas cette commande pour le moment. Vérifiez le numéro de commande et l’email saisi.';
        }

        $av = $context['availability'] ?? ['found' => false];
        if (($av['found'] ?? false) === true) {
            $stock = (float) ($av['stock'] ?? 0);
            if ($stock > 0) {
                return "{$av['name']} est disponible en stock (environ {$stock} unité(s)).";
            }
            return "{$av['name']} est actuellement en rupture de stock.";
        }
        return 'Je peux vous aider sur la disponibilité, la livraison, les retours ou le statut commande. Dites-moi votre besoin.';
    }
}

