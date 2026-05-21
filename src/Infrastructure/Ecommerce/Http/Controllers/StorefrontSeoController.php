<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\Application\Ecommerce\Services\StorefrontSeoService;

class StorefrontSeoController
{
    public function __construct(
        private StorefrontSeoService $seoService
    ) {}

    public function robots(Request $request): Response
    {
        $shop = $request->attributes->get('storefront_shop');
        if (!$shop instanceof Shop) {
            abort(404);
        }

        return response($this->seoService->robotsTxt($request, $shop), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(Request $request): Response
    {
        $shop = $request->attributes->get('storefront_shop');
        if (!$shop instanceof Shop) {
            abort(404);
        }

        return response($this->seoService->sitemapXml($request, $shop), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
