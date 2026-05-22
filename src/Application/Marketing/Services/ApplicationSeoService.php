<?php

namespace Src\Application\Marketing\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * SEO du site marketing OmniSolution (domaine principal, hors sous-domaines vitrine).
 */
final class ApplicationSeoService
{
    private const SEO_FILE = 'settings/app/seo.json';

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $base = [
            'site_name' => (string) config('app_seo.site_name', 'OmniSolution'),
            'title' => (string) config('app_seo.title', ''),
            'description' => (string) config('app_seo.description', ''),
            'keywords' => (string) config('app_seo.keywords', ''),
            'indexing_enabled' => (bool) config('app_seo.indexing_enabled', true),
            'google_site_verification' => $this->normalizeGoogleVerification(
                trim((string) config('app_seo.google_site_verification', ''))
            ),
            'og_image' => config('app_seo.og_image'),
            'twitter_handle' => config('app_seo.twitter_handle'),
            'locale' => (string) config('app_seo.locale', 'fr_FR'),
        ];

        try {
            if (Storage::disk('public')->exists(self::SEO_FILE)) {
                $raw = Storage::disk('public')->get(self::SEO_FILE);
                $stored = json_decode($raw, true);
                if (is_array($stored)) {
                    foreach (['site_name', 'title', 'description', 'keywords', 'google_site_verification', 'og_image', 'twitter_handle', 'locale'] as $key) {
                        if (array_key_exists($key, $stored) && $stored[$key] !== null && $stored[$key] !== '') {
                            $base[$key] = $stored[$key];
                        }
                    }
                    if (array_key_exists('indexing_enabled', $stored)) {
                        $base['indexing_enabled'] = (bool) $stored['indexing_enabled'];
                    }
                }
            }
        } catch (\Throwable) {
        }

        $base['google_site_verification'] = $this->normalizeGoogleVerification(
            (string) ($base['google_site_verification'] ?? '')
        );

        if ($base['og_image'] === null || $base['og_image'] === '') {
            $base['og_image'] = $this->defaultOgImageUrl();
        }

        return $base;
    }

    public function publicBaseUrl(Request $request): string
    {
        $configured = rtrim((string) config('app.url', ''), '/');
        if ($configured !== '' && !str_contains($configured, 'localhost')) {
            return $configured;
        }

        return rtrim($request->root(), '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(Request $request): array
    {
        $s = $this->settings();
        $base = $this->publicBaseUrl($request);
        $indexing = (bool) $s['indexing_enabled'];

        return [
            'siteName' => $s['site_name'],
            'title' => $this->truncate((string) $s['title'], 160),
            'description' => $this->truncate((string) $s['description'], 160),
            'keywords' => $this->truncate(trim((string) $s['keywords']), 255) ?: null,
            'robots' => $indexing ? 'index, follow' : 'noindex, nofollow',
            'canonicalUrl' => $base . '/',
            'ogType' => 'website',
            'ogImage' => $s['og_image'],
            'locale' => (string) $s['locale'],
            'indexingEnabled' => $indexing,
            'googleSiteVerification' => $s['google_site_verification'] !== '' ? $s['google_site_verification'] : null,
            'twitterHandle' => $s['twitter_handle'] ?: null,
            'publicBaseUrl' => $base,
            'sitemapUrl' => $base . '/sitemap.xml',
            'robotsUrl' => $base . '/robots.txt',
            'contactEmail' => (string) config('marketing_legal.contact_email', '') ?: null,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function buildPage(Request $request, array $overrides = []): array
    {
        $defaults = $this->defaults($request);
        $base = $defaults['publicBaseUrl'];
        $path = isset($overrides['path']) ? '/' . ltrim((string) $overrides['path'], '/') : '/';
        if ($path === '//') {
            $path = '/';
        }

        $title = trim((string) ($overrides['title'] ?? $defaults['title']));
        if ($title !== '' && !str_contains($title, (string) $defaults['siteName'])) {
            $title = $this->truncate($title . ' | ' . $defaults['siteName'], 160);
        }

        $noindex = (bool) ($overrides['noindex'] ?? false);
        $indexing = $defaults['indexingEnabled'] && !$noindex;

        return [
            'siteName' => $defaults['siteName'],
            'title' => $title !== '' ? $this->truncate($title, 160) : $defaults['title'],
            'description' => $this->truncate(
                $this->plainText((string) ($overrides['description'] ?? $defaults['description'])),
                160
            ),
            'keywords' => isset($overrides['keywords'])
                ? ($this->truncate(trim((string) $overrides['keywords']), 255) ?: null)
                : $defaults['keywords'],
            'robots' => $indexing ? 'index, follow' : 'noindex, nofollow',
            'canonicalUrl' => $base . ($path === '/' ? '' : $path),
            'ogType' => (string) ($overrides['ogType'] ?? $defaults['ogType']),
            'ogImage' => $overrides['ogImage'] ?? $defaults['ogImage'],
            'locale' => $defaults['locale'],
            'indexingEnabled' => $indexing,
            'googleSiteVerification' => $defaults['googleSiteVerification'],
            'twitterHandle' => $defaults['twitterHandle'],
            'jsonLd' => $overrides['jsonLd'] ?? null,
        ];
    }

    public function robotsTxt(Request $request): string
    {
        $s = $this->settings();
        $base = $this->publicBaseUrl($request);

        if (!($s['indexing_enabled'] ?? true)) {
            return "User-agent: *\nDisallow: /\n";
        }

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /dashboard',
            'Disallow: /admin',
            'Disallow: /pharmacy',
            'Disallow: /hardware',
            'Disallow: /commerce',
            'Disallow: /ecommerce',
            'Disallow: /login',
            'Disallow: /onboarding',
            'Disallow: /api/',
            '',
            'Sitemap: ' . $base . '/sitemap.xml',
            '',
        ]);
    }

    public function sitemapXml(Request $request): string
    {
        $s = $this->settings();
        if (!($s['indexing_enabled'] ?? true)) {
            return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
        }

        $base = $this->publicBaseUrl($request);
        $now = now()->toAtomString();
        $urls = [
            ['loc' => $base . '/', 'lastmod' => $now, 'changefreq' => 'weekly', 'priority' => '1.0'],
        ];

        foreach ((array) config('marketing_legal.pages', []) as $def) {
            if (!is_array($def) || empty($def['slug'])) {
                continue;
            }
            $urls[] = [
                'loc' => $base . '/' . ltrim((string) $def['slug'], '/'),
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>';
            $xml[] = '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1) . '</lastmod>';
            $xml[] = '    <changefreq>' . $u['changefreq'] . '</changefreq>';
            $xml[] = '    <priority>' . $u['priority'] . '</priority>';
            $xml[] = '  </url>';
        }
        $xml[] = '</urlset>';

        return implode("\n", $xml);
    }

    /**
     * @return array<string, mixed>
     */
    public function landingJsonLd(Request $request): array
    {
        $s = $this->settings();
        $base = $this->publicBaseUrl($request);

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    'name' => (string) $s['site_name'],
                    'url' => $base,
                    'logo' => $s['og_image'],
                    'description' => $this->truncate((string) $s['description'], 500),
                ],
                [
                    '@type' => 'WebSite',
                    'name' => (string) $s['site_name'],
                    'url' => $base,
                    'description' => $this->truncate((string) $s['description'], 500),
                    'inLanguage' => 'fr',
                ],
                [
                    '@type' => 'SoftwareApplication',
                    'name' => (string) $s['site_name'],
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'url' => $base,
                    'description' => $this->truncate((string) $s['description'], 500),
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '0',
                        'priceCurrency' => 'USD',
                        'description' => 'Essai gratuit disponible',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveSettings(array $data): void
    {
        $payload = [
            'site_name' => trim((string) ($data['site_name'] ?? '')),
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'keywords' => trim((string) ($data['keywords'] ?? '')),
            'indexing_enabled' => (bool) ($data['indexing_enabled'] ?? true),
            'google_site_verification' => trim((string) ($data['google_site_verification'] ?? '')),
            'og_image' => trim((string) ($data['og_image'] ?? '')) ?: null,
            'twitter_handle' => trim((string) ($data['twitter_handle'] ?? '')) ?: null,
            'locale' => trim((string) ($data['locale'] ?? 'fr_FR')) ?: 'fr_FR',
        ];

        Storage::disk('public')->put(self::SEO_FILE, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function defaultOgImageUrl(): ?string
    {
        try {
            $disk = Storage::disk('public');
            $path = 'settings/app/app-logo.png';
            if ($disk->exists($path)) {
                return $disk->url($path);
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private function normalizeGoogleVerification(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, 'google-site-verification=')) {
            return trim(substr($value, strlen('google-site-verification=')));
        }

        return $value;
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
