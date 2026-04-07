<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Résout la boutique e-commerce à partir du sous-domaine (ex: monshop.omnisolution.shop).
 * Utilisé pour la vitrine publique sans authentification.
 * Définit request()->attributes->set('storefront_shop', $shop) ou 404.
 */
class ResolveStorefrontShopBySubdomain
{
    /** Sous-domaines qui ne doivent pas être traités comme des boutiques. */
    private const RESERVED = ['www', 'api', 'app', 'admin', 'mail', 'ftp', 'cdn', 'static', 'shop', 'store', 'boutique', 'ecommerce', 'staging', 'demo', 'test', 'localhost'];

    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $request->route('subdomain');
        if ($subdomain === null || $subdomain === '') {
            abort(404, 'Sous-domaine manquant.');
        }
        $subdomain = strtolower(trim($subdomain));
        if (in_array($subdomain, self::RESERVED, true)) {
            abort(404, 'Ce sous-domaine est réservé.');
        }

        $shop = Shop::whereRaw('LOWER(ecommerce_subdomain) = ?', [$subdomain])
            ->where('ecommerce_is_online', true)
            ->first();

        if (!$shop) {
            \Log::warning('Storefront subdomain not resolved', [
                'host' => $request->getHost(),
                'path' => $request->getPathInfo(),
                'subdomain' => $subdomain,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            abort(404, 'Boutique introuvable ou non publiée.');
        }

        $request->attributes->set('storefront_shop', $shop);

        return $next($request);
    }
}
