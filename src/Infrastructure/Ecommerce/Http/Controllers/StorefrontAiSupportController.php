<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Application\Ecommerce\Services\EcommerceAiSupportAnalyticsService;
use Src\Application\Ecommerce\Services\EcommerceAiSupportFaqMatcher;
use Src\Application\Ecommerce\Services\StorefrontAiSemanticSearchService;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Services\ProductImageService;

class StorefrontAiSupportController
{
    public function __construct(
        private readonly FeatureLimitService $featureLimitService,
        private readonly StorefrontAiSemanticSearchService $semanticSearch,
        private readonly EcommerceAiSupportAnalyticsService $analytics,
        private readonly EcommerceAiSupportFaqMatcher $faqMatcher,
        private readonly ProductImageService $productImages,
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
            'history' => 'nullable|array|max:6',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:2000',
            'page_context' => 'nullable|array',
            'page_context.page_type' => 'nullable|string|in:general,product,catalog,cart',
            'page_context.product_name' => 'nullable|string|max:255',
            'page_context.product_id' => 'nullable',
        ]);

        $message = trim((string) $data['message']);
        $pageContext = is_array($data['page_context'] ?? null) ? $data['page_context'] : [];
        $topic = $this->detectTopic($message);

        $semanticEnabled = $this->semanticSearchEnabled($tenantId, $cfg);
        if ($semanticEnabled && $this->looksLikeProductSearch($message, $topic)) {
            $topic = 'product_search';
        }

        $merchantFaq = EcommerceAiSupportFaqMatcher::normalizeList($cfg['ai_support_faq'] ?? []);

        $context = [
            'shop_name' => (string) $shop->name,
            'shipping_policy' => (string) ($cfg['ai_support_shipping_policy'] ?? ''),
            'returns_policy' => (string) ($cfg['ai_support_returns_policy'] ?? ''),
            'flat_shipping_enabled' => (bool) ($cfg['storefront_use_flat_shipping'] ?? false),
            'flat_shipping_amount' => isset($cfg['storefront_flat_shipping_amount']) ? (float) $cfg['storefront_flat_shipping_amount'] : 0.0,
            'page_context' => $pageContext,
            'merchant_faq' => $merchantFaq,
        ];

        $products = [];

        if ($topic === 'availability') {
            $searchTerm = trim((string) ($pageContext['product_name'] ?? ''));
            if ($searchTerm === '') {
                $searchTerm = $message;
            }
            $context['availability'] = $this->findAvailability($shop, $searchTerm);
            if (($context['availability']['found'] ?? false) === true && isset($context['availability']['product_id'])) {
                $products = $this->buildProductCards($shop, [(string) $context['availability']['product_id']]);
            }
        }

        if ($topic === 'product_search' && $semanticEnabled) {
            try {
                $this->featureLimitService->assertCanUseMonthlyFeature($tenantId, 'ai.ecommerce.semantic_search', 'la recherche produits IA');
                $ids = $this->semanticSearch->searchProductIds((int) $shop->id, $message);
                $products = $this->buildProductCards($shop, array_slice($ids, 0, 5));
                $context['product_suggestions'] = array_map(fn ($p) => [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'currency' => $p['currency'],
                    'in_stock' => $p['in_stock'],
                ], $products);
                $this->featureLimitService->recordFeatureUsage($tenantId, 'ai.ecommerce.semantic_search');
            } catch (\Throwable) {
                $context['product_suggestions'] = [];
            }
        }

        if ($topic === 'order_status') {
            $context['order_status'] = $this->findOrderStatus(
                $shop,
                (string) ($data['order_number'] ?? ''),
                (string) ($data['customer_email'] ?? ''),
                $message
            );
        }

        $history = is_array($data['history'] ?? null) ? $data['history'] : [];

        $faqAnswer = $topic !== 'product_search'
            ? $this->faqMatcher->matchAnswer($message, $merchantFaq)
            : null;

        if ($faqAnswer !== null) {
            $answer = $faqAnswer;
        } else {
            $answer = $this->generateAnswerWithFallback(
                $message,
                $topic,
                $context,
                (string) ($cfg['ai_support_tone'] ?? 'friendly'),
                $history
            );
        }

        if ($topic === 'product_search' && count($products) > 0 && !str_contains(mb_strtolower($answer), 'catalogue')) {
            $answer .= "\n\nVoici quelques produits qui pourraient vous intéresser :";
        }

        $showCatalogLink = $topic === 'product_search' || count($products) > 0;

        $logId = $this->analytics->logAsk(
            (int) $shop->id,
            $shop->tenant_id ? (int) $shop->tenant_id : null,
            $topic,
            $message,
            $answer,
            count($products),
            $request->ip()
        );

        $this->featureLimitService->recordFeatureUsage($tenantId, 'ai.ecommerce.support');

        return response()->json([
            'answer' => $answer,
            'topic' => $topic,
            'log_id' => $logId,
            'products' => $products,
            'show_catalog_link' => $showCatalogLink,
            'from_faq' => $faqAnswer !== null,
        ]);
    }

    public function feedback(Request $request): JsonResponse
    {
        $shop = $this->resolveShop($request);
        if (!$shop) {
            return response()->json(['message' => 'Boutique introuvable.'], 404);
        }

        $data = $request->validate([
            'log_id' => 'required|uuid',
            'feedback' => 'required|in:helpful,not_helpful',
        ]);

        $ok = $this->analytics->recordFeedback(
            (string) $data['log_id'],
            (int) $shop->id,
            (string) $data['feedback']
        );

        if (!$ok) {
            return response()->json(['message' => 'Retour déjà enregistré ou conversation introuvable.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Merci pour votre retour.']);
    }

    /** @param array<string, mixed> $cfg */
    private function semanticSearchEnabled(string $tenantId, array $cfg): bool
    {
        if (!(bool) ($cfg['ai_semantic_search_enabled'] ?? false)) {
            return false;
        }

        return $this->featureLimitService->isFeatureEnabled($tenantId, 'ai.ecommerce.semantic_search');
    }

    private function looksLikeProductSearch(string $message, string $currentTopic): bool
    {
        if (in_array($currentTopic, ['shipping', 'returns', 'order_status'], true)) {
            return false;
        }
        $m = mb_strtolower($message);

        return (bool) preg_match(
            '/\b(cherche|recherche|recommand|propose|montre|trouve|besoin|idée|idee|cadeau|offrir|quel produit|quels produits|catalogue|article pour|produits pour)\b/u',
            $m
        );
    }

    /**
     * @param list<string> $productIds
     *
     * @return list<array<string, mixed>>
     */
    private function buildProductCards(Shop $shop, array $productIds): array
    {
        if ($productIds === [] || !Schema::hasTable('gc_products')) {
            return [];
        }

        $currency = (string) ($shop->currency ?? 'USD');
        $rows = ProductModel::query()
            ->where('shop_id', $shop->id)
            ->whereIn('id', $productIds)
            ->where('status', 'active')
            ->get(['id', 'name', 'sale_price_amount', 'sale_price_currency', 'stock', 'image_path', 'image_type']);

        $byId = $rows->keyBy(fn ($p) => (string) $p->id);
        $cards = [];
        foreach ($productIds as $id) {
            $p = $byId->get((string) $id);
            if ($p === null) {
                continue;
            }
            $imageUrl = null;
            if ($p->image_path) {
                try {
                    $imageUrl = $this->productImages->getUrl($p->image_path, $p->image_type ?: 'upload');
                } catch (\Throwable) {
                    $imageUrl = null;
                }
            }
            $stock = (float) ($p->stock ?? 0);
            $cards[] = [
                'id' => (string) $p->id,
                'name' => (string) $p->name,
                'price' => round((float) ($p->sale_price_amount ?? 0), 2),
                'currency' => (string) ($p->sale_price_currency ?: $currency),
                'image_url' => $imageUrl,
                'in_stock' => $stock > 0,
                'stock' => $stock,
            ];
        }

        return $cards;
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
        if (str_contains($m, 'retour') || str_contains($m, 'rembourse') || str_contains($m, 'échange') || str_contains($m, 'echange')) {
            return 'returns';
        }
        if (str_contains($m, 'livraison') || str_contains($m, 'expédition') || str_contains($m, 'expedition') || str_contains($m, 'delai') || str_contains($m, 'délai') || str_contains($m, 'frais de port') || str_contains($m, 'transport')) {
            return 'shipping';
        }
        if (str_contains($m, 'commande') || str_contains($m, 'statut') || str_contains($m, 'order') || str_contains($m, 'suivi') || preg_match('/\b(cmd|ord)[- ]?\d+/i', $message)) {
            return 'order_status';
        }
        if (str_contains($m, 'stock') || str_contains($m, 'disponib') || str_contains($m, 'rupture') || str_contains($m, 'en stock') || (str_contains($m, 'avoir') && (str_contains($m, 'produit') || str_contains($m, 'article')))) {
            return 'availability';
        }

        return 'general';
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
            ->where('status', 'active')
            ->where(function ($sub) use ($term): void {
                $sub->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%');
            })
            ->orderByDesc('stock')
            ->first(['id', 'name', 'stock', 'status']);

        if (!$q) {
            return ['found' => false];
        }

        return [
            'found' => true,
            'product_id' => (string) $q->id,
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

    /**
     * @param array<string,mixed> $context
     * @param array<int, array{role: string, content: string}> $history
     */
    private function generateAnswerWithFallback(string $message, string $topic, array $context, string $tone, array $history = []): string
    {
        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey !== '') {
            try {
                $toneLabel = $tone === 'professional' ? 'professionnel et courtois' : 'chaleureux et rassurant';
                $system = "Tu es l'assistant client de la boutique « {$context['shop_name']} ». Réponds en français, 2 à 5 phrases, concrètes, ton {$toneLabel}. "
                    ."N'invente pas de données absentes du contexte JSON. Priorise les réponses de merchant_faq si elles correspondent à la question. "
                    ."Si des product_suggestions sont présentes, mentionne-les brièvement sans lister tous les prix (le client verra les cartes produits).";
                $chatMessages = [['role' => 'system', 'content' => $system]];
                foreach ($history as $turn) {
                    $role = ($turn['role'] ?? '') === 'user' ? 'user' : 'assistant';
                    $content = trim((string) ($turn['content'] ?? ''));
                    if ($content !== '') {
                        $chatMessages[] = ['role' => $role, 'content' => $content];
                    }
                }
                $chatMessages[] = [
                    'role' => 'user',
                    'content' => "Contexte JSON:\n".json_encode($context, JSON_UNESCAPED_UNICODE)."\n\nSujet détecté: {$topic}\nQuestion actuelle: {$message}",
                ];
                $res = Http::withToken($apiKey)->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => $chatMessages,
                    'temperature' => 0.2,
                    'max_tokens' => 320,
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

        if ($topic === 'product_search') {
            $suggestions = $context['product_suggestions'] ?? [];
            if (is_array($suggestions) && count($suggestions) > 0) {
                $names = array_slice(array_map(fn ($p) => $p['name'] ?? '', $suggestions), 0, 3);
                $list = implode(', ', array_filter($names));

                return "Voici des produits correspondant à votre recherche : {$list}. Consultez les suggestions ci-dessous pour voir le détail et commander.";
            }

            return 'Je n’ai pas trouvé de produit correspondant. Essayez d’autres mots-clés ou parcourez notre catalogue.';
        }

        if ($topic === 'shipping') {
            if (($context['flat_shipping_enabled'] ?? false) === true) {
                $amount = number_format((float) ($context['flat_shipping_amount'] ?? 0), 2, ',', ' ');

                return "La boutique applique actuellement des frais de livraison fixes de {$amount}. "
                    .'Les délais précis dépendent de votre zone. '
                    .'N’hésitez pas à préciser votre ville pour une estimation plus fine.';
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

        if ($topic === 'general') {
            $shop = (string) ($context['shop_name'] ?? 'notre boutique');

            return "Je suis l'assistant de {$shop}. Je peux vous renseigner sur la livraison, les retours, la disponibilité d'un produit, vous suggérer des articles ou le suivi d'une commande. Que souhaitez-vous savoir ?";
        }

        $av = $context['availability'] ?? ['found' => false];
        if (($av['found'] ?? false) === true) {
            $stock = (float) ($av['stock'] ?? 0);
            if ($stock > 0) {
                return "{$av['name']} est disponible en stock (environ {$stock} unité(s)).";
            }

            return "{$av['name']} est actuellement en rupture de stock.";
        }

        return 'Je n’ai pas trouvé ce produit. Indiquez le nom exact ou la référence, ou parcourez le catalogue.';
    }
}
