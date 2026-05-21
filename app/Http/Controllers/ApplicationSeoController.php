<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\Application\Marketing\Services\ApplicationSeoService;

class ApplicationSeoController extends Controller
{
    public function __construct(
        private ApplicationSeoService $seoService
    ) {}

    public function robots(Request $request): Response
    {
        if ($request->attributes->get('storefront_shop') instanceof Shop) {
            abort(404);
        }

        return response($this->seoService->robotsTxt($request), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(Request $request): Response
    {
        if ($request->attributes->get('storefront_shop') instanceof Shop) {
            abort(404);
        }

        return response($this->seoService->sitemapXml($request), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
