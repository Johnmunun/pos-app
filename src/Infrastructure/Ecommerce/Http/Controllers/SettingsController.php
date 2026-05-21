<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use App\Services\TenantBackofficeShopResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Application\Ecommerce\Services\EcommerceAiSupportAnalyticsService;
use Src\Application\Ecommerce\Services\EcommerceAiSupportFaqMatcher;
use Src\Application\Ecommerce\Services\EcommerceAiSupportFaqSuggestionService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
use Src\Infrastructure\GlobalCommerce\Services\ProductImageService;

class SettingsController
{
    /** Sous-domaines réservés (ne pas autoriser pour les boutiques). */
    private const RESERVED_SUBDOMAINS = ['www', 'api', 'app', 'admin', 'mail', 'ftp', 'cdn', 'static', 'shop', 'store', 'boutique', 'ecommerce', 'staging', 'demo', 'test', 'localhost'];

    /**
     * Résout la boutique pour l'utilisateur (même logique que StorefrontController).
     */
    private function resolveShop(Request $request): Shop
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'User not authenticated.');
        }

        $userModel = \App\Models\User::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        $shop = null;

        // ROOT: prioriser la boutique storefront choisie en session
        if ($isRoot) {
            $sessionShopId = $request->session()->get('current_storefront_shop_id');
            if ($sessionShopId && is_numeric($sessionShopId)) {
                $sessionShop = Shop::find((int) $sessionShopId);
                if ($sessionShop) {
                    $shop = $sessionShop;
                }
            }
        }

        $candidateId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        if (!$shop && $candidateId !== null && $candidateId !== '') {
            $shop = Shop::find($candidateId);
        }
        if (!$shop && $user->tenant_id) {
            $shop = Shop::where('tenant_id', $user->tenant_id)->first();
        }
        if (!$shop && ($isRoot || ($userModel && $userModel->hasPermission('module.ecommerce')))) {
            $shop = Shop::orderBy('id')->first();
        }

        if (!$shop && $isRoot) {
            abort(404, 'Aucune boutique. Créez au moins une boutique pour configurer le domaine.');
        }
        if (!$shop) {
            abort(403, 'Boutique introuvable. Contactez l\'administrateur.');
        }

        return $shop;
    }

    public function index(Request $request): Response
    {
        $shop = $this->resolveShop($request);
        $baseDomain = config('services.ecommerce.base_domain', 'omnisolution.shop');

        $cfg = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($cfg)) {
            $cfg = [];
        }

        $publishedProducts = $this->loadPublishedProductsForPicker($request, $shop);
        $featuredProductIds = $this->normalizeFeaturedProductIds($cfg['featured_product_ids'] ?? []);

        return Inertia::render('Ecommerce/Settings/Index', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency' => $shop->currency ?? 'USD',
                'default_tax_rate' => $shop->default_tax_rate ? (float) $shop->default_tax_rate : 0,
                'address' => $shop->address ?? '',
                'city' => $shop->city ?? '',
                'country' => $shop->country ?? '',
                'phone' => $shop->phone ?? '',
                'email' => $shop->email ?? '',
                'ecommerce_subdomain' => $shop->ecommerce_subdomain,
                'ecommerce_is_online' => (bool) $shop->ecommerce_is_online,
            ],
            'ecommerce_base_domain' => $baseDomain,
            'storefront_use_flat_shipping' => (bool) ($cfg['storefront_use_flat_shipping'] ?? false),
            'storefront_flat_shipping_amount' => isset($cfg['storefront_flat_shipping_amount'])
                ? round((float) $cfg['storefront_flat_shipping_amount'], 2)
                : 0.0,
            'ai_support_enabled' => (bool) ($cfg['ai_support_enabled'] ?? false),
            'ai_support_tone' => (string) ($cfg['ai_support_tone'] ?? 'friendly'),
            'ai_support_shipping_policy' => (string) ($cfg['ai_support_shipping_policy'] ?? ''),
            'ai_support_returns_policy' => (string) ($cfg['ai_support_returns_policy'] ?? ''),
            'ai_support_welcome_message' => (string) ($cfg['ai_support_welcome_message'] ?? ''),
            'ai_support_faq' => EcommerceAiSupportFaqMatcher::normalizeList($cfg['ai_support_faq'] ?? []),
            'ai_semantic_search_enabled' => (bool) ($cfg['ai_semantic_search_enabled'] ?? false),
            'ai_support_stats' => app(EcommerceAiSupportAnalyticsService::class)->statsForShop((int) $shop->id),
            'featured_product_ids' => $featuredProductIds,
            'published_products' => $publishedProducts,
        ]);
    }

    /**
     * Met à jour le sous-domaine de la boutique (ex: kasashop pour kasashop.{base_domain}).
     * En production, le domaine de base est lu depuis config (ECOMmerce_BASE_DOMAIN).
     */
    public function updateDomain(Request $request): RedirectResponse
    {
        $shop = $this->resolveShop($request);

        $data = $request->validate([
            'subdomain' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/i'],
            'is_online' => ['sometimes', 'boolean'],
        ]);

        // Normaliser en minuscules (DNS insensible à la casse, cohérence en production)
        $subdomain = strtolower(trim($data['subdomain']));

        if ($subdomain === '') {
            return redirect()
                ->route('ecommerce.settings.index')
                ->withErrors(['subdomain' => 'Le sous-domaine est requis.']);
        }

        // Sous-domaines réservés (éviter conflits avec l'app en production)
        if (in_array($subdomain, self::RESERVED_SUBDOMAINS, true)) {
            return redirect()
                ->route('ecommerce.settings.index')
                ->withErrors(['subdomain' => 'Ce sous-domaine est réservé. Choisissez un autre nom.']);
        }

        // Vérifier l'unicité du sous-domaine (comparaison en minuscules)
        $exists = Shop::where('id', '!=', $shop->id)
            ->whereRaw('LOWER(ecommerce_subdomain) = ?', [$subdomain])
            ->exists();

        if ($exists) {
            return redirect()
                ->route('ecommerce.settings.index')
                ->withErrors(['subdomain' => 'Ce sous-domaine est déjà utilisé par une autre boutique.']);
        }

        $shop->ecommerce_subdomain = $subdomain;
        if (array_key_exists('is_online', $data)) {
            $shop->ecommerce_is_online = (bool) $data['is_online'];
        }
        $shop->save();

        return redirect()
            ->route('ecommerce.settings.index')
            ->with('success', 'Domaine de la boutique mis à jour. En production, configurez un enregistrement DNS (CNAME ou A) pour *.'.config('services.ecommerce.base_domain', 'omnisolution.shop'));
    }

    /**
     * Frais de livraison fixes affichés sur la vitrine (panier) — montant dans la devise par défaut du tenant.
     */
    public function updateStorefrontShipping(Request $request): RedirectResponse
    {
        $shop = $this->resolveShop($request);

        $data = $request->validate([
            'storefront_use_flat_shipping' => ['required', 'boolean'],
            'storefront_flat_shipping_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $amount = isset($data['storefront_flat_shipping_amount'])
            ? round(max(0, (float) $data['storefront_flat_shipping_amount']), 2)
            : 0.0;

        $current = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($current)) {
            $current = [];
        }

        $current['storefront_use_flat_shipping'] = (bool) $data['storefront_use_flat_shipping'];
        $current['storefront_flat_shipping_amount'] = $amount;

        $shop->ecommerce_storefront_config = $current;
        $shop->save();

        return redirect()
            ->route('ecommerce.settings.index')
            ->with('success', 'Frais de livraison vitrine enregistrés.');
    }

    public function updateAiSupport(Request $request): RedirectResponse
    {
        $shop = $this->resolveShop($request);

        $data = $request->validate([
            'ai_support_enabled' => ['required', 'boolean'],
            'ai_support_tone' => ['required', 'string', 'in:friendly,professional'],
            'ai_support_shipping_policy' => ['nullable', 'string', 'max:1500'],
            'ai_support_returns_policy' => ['nullable', 'string', 'max:1500'],
            'ai_support_welcome_message' => ['nullable', 'string', 'max:500'],
            'ai_support_faq' => ['nullable', 'array', 'max:'.EcommerceAiSupportFaqMatcher::MAX_ITEMS],
            'ai_support_faq.*.question' => ['required_with:ai_support_faq', 'string', 'max:200'],
            'ai_support_faq.*.answer' => ['required_with:ai_support_faq', 'string', 'max:800'],
            'ai_semantic_search_enabled' => ['required', 'boolean'],
        ]);

        $current = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($current)) {
            $current = [];
        }

        $current['ai_support_enabled'] = (bool) $data['ai_support_enabled'];
        $current['ai_support_tone'] = (string) $data['ai_support_tone'];
        $current['ai_support_shipping_policy'] = trim((string) ($data['ai_support_shipping_policy'] ?? ''));
        $current['ai_support_returns_policy'] = trim((string) ($data['ai_support_returns_policy'] ?? ''));
        $current['ai_support_welcome_message'] = trim((string) ($data['ai_support_welcome_message'] ?? ''));
        $current['ai_support_faq'] = EcommerceAiSupportFaqMatcher::normalizeList($data['ai_support_faq'] ?? []);
        $current['ai_semantic_search_enabled'] = (bool) $data['ai_semantic_search_enabled'];

        $shop->ecommerce_storefront_config = $current;
        $shop->save();

        return redirect()
            ->route('ecommerce.settings.index')
            ->with('success', 'Support client IA enregistré.');
    }

    public function suggestAiSupportFaq(Request $request, EcommerceAiSupportFaqSuggestionService $suggestionService): JsonResponse
    {
        $shop = $this->resolveShop($request);
        $cfg = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($cfg)) {
            $cfg = [];
        }

        $tenantId = $shop->tenant_id !== null ? (string) $shop->tenant_id : (string) $shop->id;
        $features = app(FeatureLimitService::class);
        $features->assertFeatureEnabled($tenantId, 'ai.ecommerce.support');
        $features->assertCanUseMonthlyFeature($tenantId, 'ai.ecommerce.support', 'la génération de FAQ assistant');

        $validated = $request->validate([
            'count' => 'nullable|integer|min:1|max:8',
            'shipping_policy' => 'nullable|string|max:1500',
            'returns_policy' => 'nullable|string|max:1500',
            'tone' => 'nullable|string|in:friendly,professional',
            'existing_faq' => 'nullable|array|max:'.EcommerceAiSupportFaqMatcher::MAX_ITEMS,
            'existing_faq.*.question' => 'nullable|string|max:200',
            'existing_faq.*.answer' => 'nullable|string|max:800',
        ]);

        $draftCfg = $cfg;
        if (isset($validated['shipping_policy'])) {
            $draftCfg['ai_support_shipping_policy'] = trim((string) $validated['shipping_policy']);
        }
        if (isset($validated['returns_policy'])) {
            $draftCfg['ai_support_returns_policy'] = trim((string) $validated['returns_policy']);
        }
        if (isset($validated['tone'])) {
            $draftCfg['ai_support_tone'] = (string) $validated['tone'];
        }

        $existing = EcommerceAiSupportFaqMatcher::normalizeList(
            $validated['existing_faq'] ?? ($cfg['ai_support_faq'] ?? [])
        );

        $suggestions = $suggestionService->suggest(
            $shop,
            $draftCfg,
            $existing,
            (int) ($validated['count'] ?? 5)
        );

        $features->recordFeatureUsage($tenantId, 'ai.ecommerce.support');

        return response()->json([
            'suggestions' => $suggestions,
            'message' => count($suggestions) > 0
                ? count($suggestions).' suggestion(s) générée(s). Vérifiez puis ajoutez celles à conserver.'
                : 'Aucune nouvelle suggestion (FAQ déjà complètes ou contexte insuffisant). Renseignez les politiques livraison/retours.',
        ]);
    }

    public function exportAiSupportStats(Request $request): StreamedResponse
    {
        $shop = $this->resolveShop($request);
        $days = min(365, max(7, $request->integer('days', 30)));

        $rows = app(EcommerceAiSupportAnalyticsService::class)->interactionsForExport((int) $shop->id, $days);
        $filename = 'assistant-ia-'.Str::slug($shop->name ?: 'boutique').'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Date',
                'Sujet',
                'Question client',
                'Extrait réponse',
                'Produits suggérés',
                'Feedback',
                'Date feedback',
                'IP',
            ], ';');
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->created_at?->format('d/m/Y H:i') ?? '',
                    EcommerceAiSupportAnalyticsService::topicLabel($row->topic),
                    $row->user_message ?? '',
                    $row->assistant_excerpt ?? '',
                    (int) $row->products_shown,
                    $row->feedback === 'helpful' ? 'Utile' : ($row->feedback === 'not_helpful' ? 'Pas utile' : ''),
                    $row->feedback_at?->format('d/m/Y H:i') ?? '',
                    $row->ip_address ?? '',
                ], ';');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function updateFeaturedProducts(Request $request): RedirectResponse
    {
        $shop = $this->resolveShop($request);

        $data = $request->validate([
            'featured_product_ids' => ['nullable', 'array', 'max:12'],
            'featured_product_ids.*' => ['required', 'uuid'],
        ]);

        $ids = $this->normalizeFeaturedProductIds($data['featured_product_ids'] ?? []);

        if ($ids !== [] && Schema::hasTable('gc_products')) {
            $user = $request->user();
            $tenantId = $user && $user->tenant_id !== null && $user->tenant_id !== ''
                ? (string) $user->tenant_id
                : null;
            $resolver = app(TenantBackofficeShopResolver::class);
            $productShopIds = $resolver->globalCommerceInventoryShopIds($shop, $tenantId);

            $validIds = ProductModel::query()
                ->whereIn('shop_id', $productShopIds)
                ->where('is_active', true)
                ->where('is_published_ecommerce', true)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();

            $ids = array_values(array_filter(
                $ids,
                fn (string $id) => in_array($id, $validIds, true)
            ));
        }

        $current = $shop->ecommerce_storefront_config ?? [];
        if (! is_array($current)) {
            $current = [];
        }

        $current['featured_product_ids'] = $ids;
        $shop->ecommerce_storefront_config = $current;
        $shop->save();

        return redirect()
            ->route('ecommerce.settings.index')
            ->with('success', 'Produits en vedette enregistrés.');
    }

    /**
     * @return list<array{id: string, name: string, price_amount: float, price_currency: string, image_url: string|null}>
     */
    private function loadPublishedProductsForPicker(Request $request, Shop $shop): array
    {
        if (! Schema::hasTable('gc_products')) {
            return [];
        }

        $user = $request->user();
        $tenantId = $user && $user->tenant_id !== null && $user->tenant_id !== ''
            ? (string) $user->tenant_id
            : null;
        $resolver = app(TenantBackofficeShopResolver::class);
        $productShopIds = $resolver->globalCommerceInventoryShopIds($shop, $tenantId);
        $imageService = app(ProductImageService::class);

        return ProductModel::query()
            ->whereIn('shop_id', $productShopIds)
            ->where('is_active', true)
            ->where('is_published_ecommerce', true)
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->map(function (ProductModel $model) use ($imageService) {
                $imageUrl = null;
                if ($model->image_path) {
                    try {
                        $imageUrl = $imageService->getUrl($model->image_path, $model->image_type ?: 'upload');
                    } catch (\Throwable) {
                    }
                }

                return [
                    'id' => (string) $model->id,
                    'name' => $model->name,
                    'price_amount' => (float) $model->sale_price_amount,
                    'price_currency' => (string) ($model->sale_price_currency ?? 'USD'),
                    'image_url' => $imageUrl,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $raw
     * @return list<string>
     */
    private function normalizeFeaturedProductIds(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $id) {
            $id = trim((string) $id);
            if ($id === '' || in_array($id, $ids, true)) {
                continue;
            }
            $ids[] = $id;
            if (count($ids) >= 12) {
                break;
            }
        }

        return $ids;
    }

    // Plus de mise à jour de devise ici : la devise est gérée globalement via /settings/currencies
}
