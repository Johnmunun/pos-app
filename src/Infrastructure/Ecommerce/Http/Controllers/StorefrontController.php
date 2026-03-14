<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Src\Domain\GlobalCommerce\Inventory\Repositories\ProductRepositoryInterface as GlobalCommerceProductRepositoryInterface;
use Src\Infrastructure\Ecommerce\Models\CmsBannerModel;
use Src\Infrastructure\Ecommerce\Models\CmsPageModel;
use Src\Infrastructure\Ecommerce\Models\CmsBlogArticleModel;
use Src\Application\Settings\UseCases\GetStoreSettingsUseCase;
use Src\Infrastructure\Settings\Services\StoreLogoService;
use Src\Infrastructure\Ecommerce\Services\DefaultCmsPagesService;
use Src\Infrastructure\Ecommerce\Models\PaymentMethodModel;
use Src\Infrastructure\Ecommerce\Models\ShippingMethodModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

class StorefrontController
{
    private function getPublicImageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            return null;
        }

        return $disk->url($path);
    }

    public function __construct(
        private readonly GlobalCommerceProductRepositoryInterface $productRepository
    ) {
    }
    /**
     * Résout la boutique (Shop) pour l'utilisateur connecté.
     * - Utilisateur avec shop_id ou tenant_id : boutique liée au tenant.
     * - ROOT sans boutique sélectionnée : première boutique (prévisualisation en local).
     */
    private function resolveShop(Request $request): Shop
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $userModel = \App\Models\User::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        $shop = null;

        // 1) Si l'utilisateur a un shop_id (clé primaire shops), l'utiliser
        $candidateId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        if ($candidateId !== null && $candidateId !== '') {
            $shop = Shop::find($candidateId);
        }

        // 2) Sinon, trouver une boutique par tenant_id (shops.tenant_id = user.tenant_id)
        if (!$shop && $user->tenant_id) {
            $shop = Shop::where('tenant_id', $user->tenant_id)->first();
        }

        // 3) ROOT ou utilisateur avec module e-commerce sans boutique : première boutique (prévisualisation)
        if (!$shop && ($isRoot || ($userModel && $userModel->hasPermission('module.ecommerce')))) {
            $shop = Shop::orderBy('id')->first();
        }

        if (!$shop && $isRoot) {
            abort(404, 'Aucune boutique. Créez au moins une boutique (tenant) pour prévisualiser la vitrine.');
        }
        if (!$shop) {
            abort(403, 'Boutique introuvable. Contactez l\'administrateur.');
        }

        return $shop;
    }

    private function getShopId(Request $request): string
    {
        return (string) $this->resolveShop($request)->id;
    }

    /**
     * Charge la configuration de la vitrine pour la boutique.
     *
     * @return array<string, mixed>
     */
    private function getStorefrontConfig(Shop $shop): array
    {
        $defaults = [
            'hero_badge' => 'Season Sale',
            'hero_title' => 'MEN\'S FASHION',
            'hero_subtitle' => 'Min. 35–70% Off',
            'hero_description' => 'Découvrez les dernières tendances pour votre boutique en ligne.',
            'hero_primary_label' => 'Voir la boutique',
            'hero_secondary_label' => 'En savoir plus',
            'theme_primary_color' => '#f59e0b',
            'theme_secondary_color' => '#d97706',
        ];

        $config = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($config)) {
            $config = [];
        }

        return array_merge($defaults, $config);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $shop = $this->resolveShop($request);
        $shopId = (string) $shop->id;

        // La prévisualisation est autorisée pour tout utilisateur ayant accès au storefront (même si la boutique n'est pas "en ligne")
        $config = $this->getStorefrontConfig($shop);

        // Produits en vedette (limités pour éviter les requêtes lourdes)
        $featured = [];
        $newArrivals = [];

        if (Schema::hasTable('gc_products')) {
            $baseQuery = ProductModel::query()
                ->where('shop_id', $shopId)
                ->where('is_active', true)
                ->where('is_published_ecommerce', true)
                ->where('stock', '>', 0);

            $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);

            $featuredModels = (clone $baseQuery)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();

            $newArrivalModels = (clone $baseQuery)
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get();

            $mapModel = function (ProductModel $model) use ($imageService): array {
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
                    'price_currency' => $model->sale_price_currency,
                    'image_url' => $imageUrl,
                    'is_new' => (bool) ($model->is_new ?? false),
                    'label' => $model->label ?? null,
                ];
            };

            $featured = $featuredModels->map($mapModel)->values()->toArray();
            $newArrivals = $newArrivalModels->map($mapModel)->values()->toArray();
        }

        // Bannières CMS (homepage, slider, promotion)
        $banners = [];
        if (Schema::hasTable('ecommerce_cms_banners')) {
            $bannerModels = CmsBannerModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->whereIn('position', ['homepage', 'slider', 'promotion'])
                ->orderBy('sort_order')
                ->orderBy('title')
                ->limit(6)
                ->get();
            foreach ($bannerModels as $b) {
                $url = null;
                if ($b->image_path) {
                    if (str_starts_with($b->image_path, 'http')) {
                        $url = $b->image_path;
                    } else {
                        $url = $this->getPublicImageUrl($b->image_path);
                    }
                }
                $banners[] = [
                    'id' => $b->id,
                    'title' => $b->title,
                    'image_url' => $url,
                    'link' => $b->link,
                    'position' => $b->position,
                ];
            }
        }

        // Pages CMS pour le menu/footer (actives, publiées)
        // Créer les pages par défaut automatiquement si aucune n'existe
        $cmsPages = [];
        if (Schema::hasTable('ecommerce_cms_pages')) {
            DefaultCmsPagesService::createIfEmpty((int) $shopId);

            $pageModels = CmsPageModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->orderBy('sort_order')
                ->orderBy('title')
                ->limit(10)
                ->get(['id', 'title', 'slug']);
            foreach ($pageModels as $p) {
                $cmsPages[] = ['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug];
            }
        }

        $tenantId = (string) (($user && $user->tenant_id) ? $user->tenant_id : $shopId);
        $displayCurrency = $this->getDefaultCurrencyForTenant($tenantId);

        $shopLogoUrl = $this->getShopLogoUrl($shopId);
        $storefrontConfig = $this->getStorefrontConfig($shop);

        return Inertia::render('Ecommerce/Storefront', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency' => $displayCurrency,
                'logo_url' => $shopLogoUrl,
            ],
            'config' => $config,
            'featuredProducts' => $featured,
            'newArrivals' => $newArrivals,
            'banners' => $banners,
            'cmsPages' => $cmsPages,
            'whatsapp' => [
                'number' => $storefrontConfig['whatsapp_number'] ?? null,
                'enabled' => (bool) ($storefrontConfig['whatsapp_support_enabled'] ?? false),
            ],
        ]);
    }

    public function cms(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $shop = Shop::find($shopId);
        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $config = $this->getStorefrontConfig($shop);

        $shopLogoUrl = $this->getShopLogoUrl((string) $shopId);

        return Inertia::render('Ecommerce/StorefrontCms', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'logo_url' => $shopLogoUrl,
            ],
            'config' => $config,
        ]);
    }

    public function updateCms(Request $request): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $shop = Shop::find($shopId);
        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $data = $request->validate([
            'hero_badge' => 'nullable|string|max:100',
            'hero_title' => 'nullable|string|max:150',
            'hero_subtitle' => 'nullable|string|max:150',
            'hero_description' => 'nullable|string|max:255',
            'hero_primary_label' => 'nullable|string|max:100',
            'hero_secondary_label' => 'nullable|string|max:100',
            'social_facebook_url' => 'nullable|string|max:255',
            'social_instagram_url' => 'nullable|string|max:255',
            'social_tiktok_url' => 'nullable|string|max:255',
            'social_youtube_url' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:30',
            'whatsapp_support_enabled' => 'sometimes|boolean',
            // Couleurs hex au format #RRGGBB (ou null)
            'theme_primary_color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_secondary_color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $current = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($current)) {
            $current = [];
        }

        // Normaliser la valeur booléenne
        if (!array_key_exists('whatsapp_support_enabled', $data)) {
            $data['whatsapp_support_enabled'] = $current['whatsapp_support_enabled'] ?? false;
        }

        // Ne pas écraser les couleurs si chaîne vide
        foreach (['theme_primary_color', 'theme_secondary_color'] as $key) {
            if (isset($data[$key]) && (string) $data[$key] === '') {
                unset($data[$key]);
            }
        }

        $shop->ecommerce_storefront_config = array_merge($current, $data);
        $shop->save();

        return redirect()->route('ecommerce.storefront.cms')
            ->with('success', 'Configuration de la vitrine mise à jour.');
    }

    /**
     * Affiche une page CMS par slug (Accueil, À propos, Contact, etc.)
     */
    public function showPage(Request $request, string $slug): Response
    {
        $shopId = $this->getShopId($request);

        $page = null;
        if (Schema::hasTable('ecommerce_cms_pages')) {
            $page = CmsPageModel::where('shop_id', $shopId)
                ->where('slug', $slug)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->first();
        }

        if (!$page) {
            abort(404, 'Page non trouvée');
        }

        $shop = Shop::find($shopId);
        $imageUrl = null;
        if ($page->image_path) {
            if (str_starts_with($page->image_path, 'http')) {
                $imageUrl = $page->image_path;
            } elseif (Storage::disk('public')->exists($page->image_path)) {
                $imageUrl = Storage::disk('public')->url($page->image_path);
            }
        }

        $user = $request->user();
        $tenantId = (string) (($user && $user->tenant_id) ? $user->tenant_id : $shopId);
        $displayCurrency = $this->getDefaultCurrencyForTenant($tenantId);
        $shopLogoUrl = $this->getShopLogoUrl($shopId);
        $storefrontConfig = $this->getStorefrontConfig($shop);

        return Inertia::render('Ecommerce/StorefrontPage', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency' => $displayCurrency,
                'logo_url' => $shopLogoUrl,
            ],
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'template' => $page->template ?? 'standard',
                'content' => $page->content,
                'image_url' => $imageUrl,
                'metadata' => $page->metadata ?? [],
            ],
            'cmsPages' => $this->getCmsPagesForNav($shopId),
            'whatsapp' => [
                'number' => $storefrontConfig['whatsapp_number'] ?? null,
                'enabled' => (bool) ($storefrontConfig['whatsapp_support_enabled'] ?? false),
            ],
        ]);
    }

    /**
     * Catalogue vitrine (recherche + filtre catégories) – sans AppLayout.
     */
    public function catalog(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $shop = Shop::find($shopId);
        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $categoryId = $request->input('category_id');
        $search = $request->input('search');

        $products = $this->productRepository->search($shopId, $search ?? '', array_filter([
            'category_id' => $categoryId,
            'is_active' => true,
            'is_published_ecommerce' => true,
        ]));

        $productIds = array_map(fn ($p) => $p->getId(), $products);
        $imageMap = [];
        $models = collect();
        if (!empty($productIds)) {
            $models = ProductModel::whereIn('id', $productIds)->get();
            foreach ($models as $model) {
                if ($model->image_path) {
                    try {
                        $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);
                        $imageMap[$model->id] = $imageService->getUrl($model->image_path, $model->image_type ?: 'upload');
                    } catch (\Throwable) {
                        $imageMap[$model->id] = null;
                    }
                }
            }
        }

        $modelsById = $models->keyBy('id');
        $productsData = array_map(function ($product) use ($imageMap, $modelsById) {
            $model = $modelsById[$product->getId()] ?? null;
            $productType = $model->product_type ?? 'physical';
            $isDigital = $productType === 'digital';
            $galleryUrls = [];
            if ($model && is_array($model->extra_images ?? null) && !empty($model->extra_images)) {
                $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);
                foreach ($model->extra_images as $extraPath) {
                    try {
                        $galleryUrls[] = $imageService->getUrl($extraPath, 'upload');
                    } catch (\Throwable) {
                    }
                }
            }

            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price_amount' => $product->getSalePrice()->getAmount(),
                'price_currency' => $product->getSalePrice()->getCurrency(),
                'stock' => $product->getStock()->getValue(),
                'category_id' => $product->getCategoryId(),
                'image_url' => $imageMap[$product->getId()] ?? null,
                'gallery_urls' => $galleryUrls,
                'sku' => $product->getSku(),
                'product_type' => $productType,
                'is_digital' => $isDigital,
                'discount_percent' => $model ? $model->discount_percent : null,
                'has_promotion' => ($model && $model->discount_percent) ? ($model->discount_percent > 0) : false,
                'download_url' => $isDigital && $model ? ($model->download_url ?? $model->download_path ?? null) : null,
                'requires_shipping' => (bool) ($model ? ($model->requires_shipping ?? true) : true),
            ];
        }, $products);

        $categoriesData = [];
        try {
            $categoryModels = \Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
            $categoriesData = $categoryModels
                ->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->name])
                ->toArray();
        } catch (\Throwable) {
        }

        $user = $request->user();
        $tenantId = (string) (($user && $user->tenant_id) ? $user->tenant_id : $shopId);
        $displayCurrency = $this->getDefaultCurrencyForTenant($tenantId);
        $shopLogoUrl = $this->getShopLogoUrl($shopId);

        // Bannières pour le header du catalogue (slider + promotion)
        $banners = [];
        if (Schema::hasTable('ecommerce_cms_banners')) {
            $bannerModels = CmsBannerModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->whereIn('position', ['slider', 'promotion'])
                ->orderBy('sort_order')
                ->orderBy('title')
                ->limit(6)
                ->get();

            foreach ($bannerModels as $b) {
                $url = null;
                if ($b->image_path) {
                    if (str_starts_with($b->image_path, 'http')) {
                        $url = $b->image_path;
                    } elseif (Storage::disk('public')->exists($b->image_path)) {
                        $url = Storage::disk('public')->url($b->image_path);
                    }
                }
                $banners[] = [
                    'id' => $b->id,
                    'title' => $b->title,
                    'image_url' => $url,
                    'link' => $b->link,
                    'position' => $b->position,
                ];
            }
        }

        $storefrontConfig = $this->getStorefrontConfig($shop);

        return Inertia::render('Ecommerce/StorefrontCatalog', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency' => $displayCurrency,
                'logo_url' => $shopLogoUrl,
            ],
            'products' => $productsData,
            'categories' => $categoriesData,
            'filters' => ['category_id' => $categoryId, 'search' => $search],
            'cmsPages' => $this->getCmsPagesForNav($shopId),
            'banners' => $banners,
            'whatsapp' => [
                'number' => $storefrontConfig['whatsapp_number'] ?? null,
                'enabled' => (bool) ($storefrontConfig['whatsapp_support_enabled'] ?? false),
            ],
        ]);
    }

    /**
     * Détail produit vitrine – page professionnelle sans AppLayout.
     */
    public function showProduct(Request $request, string $id): Response
    {
        $shopId = $this->getShopId($request);
        $shop = Shop::find($shopId);
        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $product = $this->productRepository->findById($id);
        if (!$product) {
            abort(404, 'Produit introuvable.');
        }

        $productModel = ProductModel::find($id);
        $typeProduit = $productModel->type_produit ?? 'physique';
        $productType = $productModel->product_type ?? 'physical';
        $isDigital = $typeProduit === 'numerique' || $productType === 'digital';

        $imageUrl = null;
        $galleryUrls = [];
        if ($productModel?->image_path) {
            try {
                $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);
                $imageUrl = $imageService->getUrl($productModel->image_path, $productModel->image_type ?: 'upload');
            } catch (\Throwable) {
            }
        }
        if ($productModel && is_array($productModel->extra_images ?? null) && !empty($productModel->extra_images)) {
            $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);
            foreach ($productModel->extra_images as $extraPath) {
                try {
                    $galleryUrls[] = $imageService->getUrl($extraPath, 'upload');
                } catch (\Throwable) {
                }
            }
        }

        $downloadUrl = null;
        if ($isDigital && $productModel) {
            $downloadUrl = $productModel->lien_telechargement ?? $productModel->download_url ?? ($productModel->download_path ? asset('storage/' . $productModel->download_path) : null);
        }

        $productData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price_amount' => $product->getSalePrice()->getAmount(),
            'price_currency' => $product->getSalePrice()->getCurrency(),
            'stock' => $product->getStock()->getValue(),
            'category_id' => $product->getCategoryId(),
            'image_url' => $imageUrl,
            'gallery_urls' => $galleryUrls,
            'sku' => $product->getSku(),
            'product_type' => $productType,
            'type_produit' => $typeProduit,
            'mode_paiement' => $productModel->mode_paiement ?? 'paiement_immediat',
            'couleur' => $productModel->couleur ?? null,
            'taille' => $productModel->taille ?? null,
            'is_digital' => $isDigital,
            'download_url' => $downloadUrl,
            'requires_shipping' => (bool) ($productModel->requires_shipping ?? !$isDigital),
            'discount_percent' => $productModel->discount_percent ?? null,
            'has_promotion' => ($productModel->discount_percent ?? 0) > 0,
        ];

        $reviews = [];
        if (Schema::hasTable('ecommerce_reviews')) {
            $reviews = \Src\Infrastructure\Ecommerce\Models\ReviewModel::where('product_id', $id)
                ->where('shop_id', $shopId)
                ->where('is_approved', true)
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'customer_name' => $r->customer_name,
                    'rating' => $r->rating,
                    'title' => $r->title,
                    'comment' => $r->comment,
                    'created_at' => $r->created_at?->format('d/m/Y'),
                ])
                ->toArray();
        }

        $user = $request->user();
        $tenantId = (string) (($user && $user->tenant_id) ? $user->tenant_id : $shopId);
        $displayCurrency = $this->getDefaultCurrencyForTenant($tenantId);
        $shopLogoUrl = $this->getShopLogoUrl($shopId);
        $storefrontConfig = $this->getStorefrontConfig($shop);

        return Inertia::render('Ecommerce/StorefrontProductShow', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency' => $displayCurrency,
                'logo_url' => $shopLogoUrl,
            ],
            'product' => $productData,
            'reviews' => $reviews,
            'cmsPages' => $this->getCmsPagesForNav($shopId),
            'whatsapp' => [
                'number' => $storefrontConfig['whatsapp_number'] ?? null,
                'enabled' => (bool) ($storefrontConfig['whatsapp_support_enabled'] ?? false),
            ],
        ]);
    }

    /**
     * Page panier vitrine – même données que Cart/Index mais layout vitrine.
     */
    public function cart(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $shop = Shop::find($shopId);
        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $user = $request->user();
        $tenantId = (int) (($user && $user->tenant_id) ? $user->tenant_id : $shopId);
        $taxRate = $shop->default_tax_rate ? (float) $shop->default_tax_rate : 0;

        $currencyConfig = $this->getCurrencyAndRates((string) $tenantId);
        $currency = $currencyConfig['currency'];
        $exchangeRates = $currencyConfig['exchange_rates'];

        $shippingMethods = ShippingMethodModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'base_cost', 'free_shipping_threshold', 'estimated_days_min', 'estimated_days_max'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'type' => $m->type,
                'base_cost' => (float) $m->base_cost,
                'free_shipping_threshold' => $m->free_shipping_threshold ? (float) $m->free_shipping_threshold : null,
                'estimated_days_min' => $m->estimated_days_min,
                'estimated_days_max' => $m->estimated_days_max,
            ])
            ->values()
            ->toArray();

        $paymentMethods = PaymentMethodModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'code' => $m->code,
                'type' => $m->type,
            ])
            ->values()
            ->toArray();

        $products = $this->getProductsForOrderDrawer($shopId);

        // Devise depuis settings/currencies (Currency model) - pas shop.currency
        $displayCurrency = strtoupper($currency);
        $shopLogoUrl = $this->getShopLogoUrl($shopId);
        $storefrontConfig = $this->getStorefrontConfig($shop);

        return Inertia::render('Ecommerce/StorefrontCart', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency' => $displayCurrency,
                'logo_url' => $shopLogoUrl,
            ],
            'cmsPages' => $this->getCmsPagesForNav($shopId),
            'shipping_methods' => $shippingMethods,
            'payment_methods' => $paymentMethods,
            'tax_rate' => $taxRate,
            'currency' => $displayCurrency,
            'exchange_rates' => $exchangeRates,
            'products' => $products,
            'config' => $storefrontConfig,
            'whatsapp' => [
                'number' => $storefrontConfig['whatsapp_number'] ?? null,
                'enabled' => (bool) ($storefrontConfig['whatsapp_support_enabled'] ?? false),
            ],
        ]);
    }

    /**
     * Devise par défaut depuis settings/currencies (table currencies).
     */
    private function getDefaultCurrencyForTenant(string $tenantId): string
    {
        $currencies = Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get();
        $default = $currencies->firstWhere('is_default', true) ?? $currencies->first();

        return $default ? strtoupper($default->code) : 'USD';
    }

    private function getCurrencyAndRates(string $tenantId): array
    {
        $currenciesList = Currency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get();
        $defaultCurrencyModel = $currenciesList->firstWhere('is_default', true) ?? $currenciesList->first();
        $defaultCode = $defaultCurrencyModel ? strtoupper($defaultCurrencyModel->code) : 'USD';

        $exchangeRatesMap = [$defaultCode => 1.0];
        if ($defaultCurrencyModel) {
            foreach ($currenciesList as $c) {
                $code = strtoupper($c->code);
                if ($code === $defaultCode) {
                    continue;
                }
                $fromDefault = ExchangeRate::where('tenant_id', $tenantId)
                    ->where('from_currency_id', $defaultCurrencyModel->id)
                    ->where('to_currency_id', $c->id)
                    ->orderByDesc('effective_date')
                    ->first();
                if ($fromDefault && (float) $fromDefault->rate > 0) {
                    $exchangeRatesMap[$code] = (float) $fromDefault->rate;
                } else {
                    $toDefault = ExchangeRate::where('tenant_id', $tenantId)
                        ->where('from_currency_id', $c->id)
                        ->where('to_currency_id', $defaultCurrencyModel->id)
                        ->orderByDesc('effective_date')
                        ->first();
                    if ($toDefault && (float) $toDefault->rate > 0) {
                        $exchangeRatesMap[$code] = 1.0 / (float) $toDefault->rate;
                    } else {
                        $exchangeRatesMap[$code] = 1.0;
                    }
                }
            }
        }

        return ['currency' => $defaultCode, 'exchange_rates' => $exchangeRatesMap];
    }

    private function getProductsForOrderDrawer(string $shopId): array
    {
        $products = ProductModel::where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(200)
            ->get();

        $imageService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);
        return $products->map(function ($p) use ($imageService) {
            $url = null;
            if ($p->image_path) {
                try {
                    $url = $imageService->getUrl($p->image_path, $p->image_type ?: 'upload');
                } catch (\Throwable) {
                }
            }
            return [
                'id' => $p->id,
                'name' => $p->name,
                'price_amount' => (float) ($p->sale_price_amount ?? $p->purchase_price_amount ?? 0),
                'price_currency' => $p->sale_price_currency ?? $p->purchase_price_currency ?? 'USD',
                'image_url' => $url,
            ];
        })->toArray();
    }

    private function getCmsPagesForNav(string $shopId): array
    {
        if (!Schema::hasTable('ecommerce_cms_pages')) {
            return [];
        }

        return CmsPageModel::where('shop_id', $shopId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(10)
            ->get(['id', 'title', 'slug'])
            ->map(fn ($p) => ['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug])
            ->toArray();
    }

    private function getShopLogoUrl(string $shopId): ?string
    {
        try {
            /** @var GetStoreSettingsUseCase $getSettings */
            $getSettings = app(GetStoreSettingsUseCase::class);
            $settings = $getSettings->execute((string) $shopId);
            if (!$settings) {
                return null;
            }

            /** @var StoreLogoService $logoService */
            $logoService = app(StoreLogoService::class);

            return $logoService->getUrl($settings->getLogoPath());
        } catch (\Throwable $e) {
            \Log::warning('Error getting storefront shop logo', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function blog(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $shop = Shop::find($shopId);
        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $articles = [];
        if (Schema::hasTable('ecommerce_cms_blog_articles')) {
            $articles = CmsBlogArticleModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->limit(30)
                ->get()
                ->map(function (CmsBlogArticleModel $article) {
                    $coverUrl = null;
                    if ($article->image_path) {
                        try {
                            $disk = $article->disk ?: 'public';
                            if (Storage::disk($disk)->exists($article->image_path)) {
                                $coverUrl = Storage::disk($disk)->url($article->image_path);
                            }
                        } catch (\Throwable) {
                        }
                    }

                    return [
                        'id' => $article->id,
                        'title' => $article->title,
                        'slug' => $article->slug,
                        'excerpt' => $article->excerpt,
                        'cover_url' => $coverUrl,
                        'published_at' => optional($article->published_at)->format('d/m/Y'),
                    ];
                })
                ->toArray();
        }

        $user = $request->user();
        $tenantId = (string) (($user && $user->tenant_id) ? $user->tenant_id : $shopId);
        $displayCurrency = $this->getDefaultCurrencyForTenant($tenantId);
        $shopLogoUrl = $this->getShopLogoUrl($shopId);
        $storefrontConfig = $this->getStorefrontConfig($shop);

        return Inertia::render('Ecommerce/StorefrontBlog', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency' => $displayCurrency,
                'logo_url' => $shopLogoUrl,
            ],
            'articles' => $articles,
            'cmsPages' => $this->getCmsPagesForNav($shopId),
            'whatsapp' => [
                'number' => $storefrontConfig['whatsapp_number'] ?? null,
                'enabled' => (bool) ($storefrontConfig['whatsapp_support_enabled'] ?? false),
            ],
        ]);
    }

    public function blogShow(Request $request, string $slug): Response
    {
        $shopId = $this->getShopId($request);
        $shop = Shop::find($shopId);
        if (!$shop) {
            abort(404, 'Shop not found');
        }

        $article = null;
        if (Schema::hasTable('ecommerce_cms_blog_articles')) {
            $article = CmsBlogArticleModel::where('shop_id', $shopId)
                ->where('slug', $slug)
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->first();
        }

        if (!$article) {
            abort(404, 'Article non trouvé');
        }

        $coverUrl = null;
        if ($article->image_path) {
            try {
                $disk = $article->disk ?: 'public';
                if (Storage::disk($disk)->exists($article->image_path)) {
                    $coverUrl = Storage::disk($disk)->url($article->image_path);
                }
            } catch (\Throwable) {
            }
        }

        $user = $request->user();
        $tenantId = (string) (($user && $user->tenant_id) ? $user->tenant_id : $shopId);
        $displayCurrency = $this->getDefaultCurrencyForTenant($tenantId);
        $shopLogoUrl = $this->getShopLogoUrl($shopId);
        $storefrontConfig = $this->getStorefrontConfig($shop);

        return Inertia::render('Ecommerce/StorefrontBlogShow', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency' => $displayCurrency,
                'logo_url' => $shopLogoUrl,
            ],
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'content' => $article->content,
                'excerpt' => $article->excerpt,
                'cover_url' => $coverUrl,
                'published_at' => optional($article->published_at)->format('d/m/Y'),
            ],
            'cmsPages' => $this->getCmsPagesForNav($shopId),
            'whatsapp' => [
                'number' => $storefrontConfig['whatsapp_number'] ?? null,
                'enabled' => (bool) ($storefrontConfig['whatsapp_support_enabled'] ?? false),
            ],
        ]);
    }
}

