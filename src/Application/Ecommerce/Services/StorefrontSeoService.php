<?php

namespace Src\Application\Ecommerce\Services;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Src\Infrastructure\Ecommerce\Models\CmsBlogArticleModel;
use Src\Infrastructure\Ecommerce\Models\CmsPageModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;

/**
 * SEO vitrine publique : titres, meta, canonical, sitemap, robots.
 */
final class StorefrontSeoService
{
    public function publicBaseUrl(Request $request, Shop $shop): string
    {
        if ($request->attributes->get('storefront_shop') instanceof Shop) {
            return rtrim($request->root(), '/');
        }

        $subdomain = trim((string) ($shop->ecommerce_subdomain ?? ''));
        if ($subdomain !== '') {
            $baseDomain = config('services.ecommerce.base_domain', 'omnisolution.shop');

            return 'https://' . $subdomain . '.' . $baseDomain;
        }

        return rtrim($request->root(), '/');
    }

    /**
     * @return array{
     *   siteName: string,
     *   title: string,
     *   description: string,
     *   keywords: string|null,
     *   robots: string,
     *   canonicalUrl: string,
     *   ogType: string,
     *   ogImage: string|null,
     *   locale: string,
     *   indexingEnabled: bool
     * }
     */
    public function shopDefaults(Request $request, Shop $shop): array
    {
        $cfg = $shop->ecommerce_storefront_config ?? [];
        if (!is_array($cfg)) {
            $cfg = [];
        }

        $siteName = trim((string) ($shop->name ?? 'Boutique'));
        $title = trim((string) ($cfg['seo_title'] ?? ''));
        if ($title === '') {
            $title = $siteName . ' — Boutique en ligne';
        }
        $description = trim((string) ($cfg['seo_description'] ?? ''));
        if ($description === '') {
            $description = 'Découvrez les produits de ' . $siteName . '. Livraison et paiement selon les options de la boutique.';
        }
        $indexing = (bool) ($cfg['seo_indexing_enabled'] ?? true);
        $base = $this->publicBaseUrl($request, $shop);
        $logo = $this->resolveShopLogoUrl($shop);

        return [
            'siteName' => $siteName,
            'title' => $this->truncate($title, 150),
            'description' => $this->truncate($description, 160),
            'keywords' => $this->truncate(trim((string) ($cfg['seo_keywords'] ?? '')), 255) ?: null,
            'robots' => $indexing ? 'index, follow' : 'noindex, nofollow',
            'canonicalUrl' => $base . '/',
            'ogType' => 'website',
            'ogImage' => $logo,
            'locale' => 'fr_FR',
            'indexingEnabled' => $indexing,
        ];
    }

    /**
     * @param array<string, mixed> $overrides title, description, keywords, path, ogType, ogImage, noindex, jsonLd
     * @return array<string, mixed>
     */
    public function buildPage(Request $request, Shop $shop, array $overrides = []): array
    {
        $defaults = $this->shopDefaults($request, $shop);
        $base = $this->publicBaseUrl($request, $shop);
        $path = isset($overrides['path']) ? '/' . ltrim((string) $overrides['path'], '/') : '/';
        if ($path === '//') {
            $path = '/';
        }

        $title = trim((string) ($overrides['title'] ?? $defaults['title']));
        if ($title !== '' && !str_contains($title, $defaults['siteName'])) {
            $title = $this->truncate($title . ' | ' . $defaults['siteName'], 160);
        }

        $description = $this->truncate(
            $this->plainText((string) ($overrides['description'] ?? $defaults['description'])),
            160
        );

        $noindex = (bool) ($overrides['noindex'] ?? false);
        $indexing = $defaults['indexingEnabled'] && !$noindex;
        $robots = $indexing ? 'index, follow' : 'noindex, nofollow';

        $canonical = $base . ($path === '/' ? '' : $path);

        $seo = [
            'siteName' => $defaults['siteName'],
            'title' => $title !== '' ? $this->truncate($title, 160) : $defaults['title'],
            'description' => $description,
            'keywords' => isset($overrides['keywords'])
                ? ($this->truncate(trim((string) $overrides['keywords']), 255) ?: null)
                : $defaults['keywords'],
            'robots' => $robots,
            'canonicalUrl' => $canonical,
            'ogType' => (string) ($overrides['ogType'] ?? $defaults['ogType']),
            'ogImage' => $overrides['ogImage'] ?? $defaults['ogImage'],
            'locale' => $defaults['locale'],
            'indexingEnabled' => $indexing,
            'jsonLd' => $overrides['jsonLd'] ?? null,
        ];

        return $seo;
    }

    public function robotsTxt(Request $request, Shop $shop): string
    {
        $cfg = $shop->ecommerce_storefront_config ?? [];
        $indexing = is_array($cfg) ? (bool) ($cfg['seo_indexing_enabled'] ?? true) : true;
        $base = $this->publicBaseUrl($request, $shop);

        if (!$indexing) {
            return "User-agent: *\nDisallow: /\n";
        }

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /cart',
            '',
            'Sitemap: ' . $base . '/sitemap.xml',
            '',
        ]);
    }

    public function sitemapXml(Request $request, Shop $shop): string
    {
        $cfg = $shop->ecommerce_storefront_config ?? [];
        if (is_array($cfg) && !($cfg['seo_indexing_enabled'] ?? true)) {
            return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
        }

        $base = $this->publicBaseUrl($request, $shop);
        $tenantId = $shop->tenant_id !== null ? (string) $shop->tenant_id : (string) $shop->id;
        $productShopIds = [(string) $shop->id];
        if ($shop->tenant_id) {
            $productShopIds = Shop::query()
                ->where('tenant_id', $shop->tenant_id)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();
        }

        $urls = [];
        $push = function (string $loc, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.5') use (&$urls): void {
            $urls[] = [
                'loc' => $loc,
                'lastmod' => $lastmod,
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        };

        $now = now()->toAtomString();
        $push($base . '/', $now, 'daily', '1.0');
        $push($base . '/catalog', $now, 'daily', '0.9');
        $push($base . '/blog', $now, 'weekly', '0.7');

        if (Schema::hasTable('ecommerce_cms_pages')) {
            $pages = CmsPageModel::query()
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->get(['slug', 'updated_at']);
            foreach ($pages as $page) {
                $slug = trim((string) ($page->slug ?? ''));
                if ($slug === '') {
                    continue;
                }
                $push(
                    $base . '/page/' . rawurlencode($slug),
                    $page->updated_at?->toAtomString(),
                    'monthly',
                    '0.6'
                );
            }
        }

        if (Schema::hasTable('ecommerce_cms_blog_articles')) {
            $articles = CmsBlogArticleModel::query()
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->get(['slug', 'updated_at']);
            foreach ($articles as $article) {
                $slug = trim((string) ($article->slug ?? ''));
                if ($slug === '') {
                    continue;
                }
                $push(
                    $base . '/blog/' . rawurlencode($slug),
                    $article->updated_at?->toAtomString(),
                    'weekly',
                    '0.6'
                );
            }
        }

        if (Schema::hasTable('gc_products')) {
            $products = ProductModel::query()
                ->whereIn('shop_id', $productShopIds)
                ->where('is_active', true)
                ->where('is_published_ecommerce', true)
                ->orderByDesc('updated_at')
                ->limit(5000)
                ->get(['id', 'updated_at']);
            foreach ($products as $product) {
                $push(
                    $base . '/product/' . rawurlencode((string) $product->id),
                    $product->updated_at?->toAtomString(),
                    'weekly',
                    '0.8'
                );
            }
        }

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>';
            if ($u['lastmod']) {
                $xml[] = '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1) . '</lastmod>';
            }
            $xml[] = '    <changefreq>' . $u['changefreq'] . '</changefreq>';
            $xml[] = '    <priority>' . $u['priority'] . '</priority>';
            $xml[] = '  </url>';
        }
        $xml[] = '</urlset>';

        return implode("\n", $xml);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function productJsonLd(Request $request, Shop $shop, array $product): ?array
    {
        $name = (string) ($product['name'] ?? '');
        if ($name === '') {
            return null;
        }
        $price = (float) ($product['price_amount'] ?? 0);
        $currency = (string) ($product['price_currency'] ?? $shop->currency ?? 'CDF');
        $inStock = ((float) ($product['stock'] ?? 0)) > 0 || !empty($product['is_digital']);

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'description' => $this->truncate($this->plainText((string) ($product['description'] ?? '')), 5000),
            'sku' => (string) ($product['sku'] ?? ''),
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format($price, 2, '.', ''),
                'priceCurrency' => $currency,
                'availability' => $inStock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url' => $this->publicBaseUrl($request, $shop) . '/product/' . rawurlencode((string) ($product['id'] ?? '')),
            ],
        ];
        if (!empty($product['image_url'])) {
            $data['image'] = [$product['image_url']];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function cmsPageJsonLd(Request $request, Shop $shop, CmsPageModel $page, ?string $imageUrl = null): ?array
    {
        $title = trim((string) $page->title);
        if ($title === '') {
            return null;
        }
        $meta = is_array($page->metadata) ? $page->metadata : [];
        $seoTitle = trim((string) ($meta['seo_title'] ?? ''));
        $seoDesc = trim((string) ($meta['seo_description'] ?? ''));
        $description = $seoDesc !== ''
            ? $seoDesc
            : $this->truncate($this->plainText((string) ($page->content ?? '')), 5000);

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $seoTitle !== '' ? $seoTitle : $title,
            'description' => $description,
            'url' => $this->publicBaseUrl($request, $shop) . '/page/' . rawurlencode((string) $page->slug),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => (string) $shop->name,
                'url' => $this->publicBaseUrl($request, $shop),
            ],
        ];
        if ($imageUrl) {
            $data['primaryImageOfPage'] = $imageUrl;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function organizationJsonLd(Request $request, Shop $shop): array
    {
        $base = $this->publicBaseUrl($request, $shop);
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => (string) $shop->name,
            'url' => $base,
        ];
        $logo = $this->resolveShopLogoUrl($shop);
        if ($logo) {
            $data['logo'] = $logo;
        }

        return $data;
    }

    private function resolveShopLogoUrl(Shop $shop): ?string
    {
        try {
            $settings = \Src\Infrastructure\GlobalCommerce\Support\GcShopResolver::shopSettings($shop);
            if (!$settings || !$settings->getLogoPath()) {
                return null;
            }
            $logoService = app(\Src\Infrastructure\GlobalCommerce\Services\ProductImageService::class);

            return $logoService->getUrl($settings->getLogoPath(), 'upload');
        } catch (\Throwable) {
            return null;
        }
    }

    private function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    private function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max - 1) . '…';
    }
}
