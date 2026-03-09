<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\CmsBannerModel;
use Src\Infrastructure\Ecommerce\Models\CmsPageModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

class StorefrontController
{
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $userModel = \App\Models\User::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }
        return (string) $shopId;
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
        ];

        $config = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($config)) {
            $config = [];
        }

        return array_merge($defaults, $config);
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $shop = Shop::find($shopId);

        if (!$shop) {
            abort(404, 'Shop not found');
        }

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
                    'is_new' => (bool) $model->is_new ?? false,
                    'label' => $model->label ?? null,
                ];
            };

            $featured = $featuredModels->map($mapModel)->values()->toArray();
            $newArrivals = $newArrivalModels->map($mapModel)->values()->toArray();
        }

        // Bannières CMS (homepage, slider)
        $banners = [];
        if (Schema::hasTable('ecommerce_cms_banners')) {
            $bannerModels = CmsBannerModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->whereIn('position', ['homepage', 'slider'])
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

        // Pages CMS pour le menu/footer (actives, publiées)
        $cmsPages = [];
        if (Schema::hasTable('ecommerce_cms_pages')) {
            $pageModels = CmsPageModel::where('shop_id', $shopId)
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderBy('sort_order')
                ->orderBy('title')
                ->limit(10)
                ->get(['id', 'title', 'slug']);
            foreach ($pageModels as $p) {
                $cmsPages[] = ['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug];
            }
        }

        return Inertia::render('Ecommerce/Storefront', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency' => $shop->currency ?? 'CDF',
            ],
            'config' => $config,
            'featuredProducts' => $featured,
            'newArrivals' => $newArrivals,
            'banners' => $banners,
            'cmsPages' => $cmsPages,
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

        return Inertia::render('Ecommerce/StorefrontCms', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
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
        ]);

        $current = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($current)) {
            $current = [];
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
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
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

        return Inertia::render('Ecommerce/StorefrontPage', [
            'shop' => ['id' => $shop->id, 'name' => $shop->name, 'currency' => $shop->currency ?? 'CDF'],
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'image_url' => $imageUrl,
            ],
        ]);
    }
}

