<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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
            'ai_semantic_search_enabled' => (bool) ($cfg['ai_semantic_search_enabled'] ?? false),
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
        $current['ai_semantic_search_enabled'] = (bool) $data['ai_semantic_search_enabled'];

        $shop->ecommerce_storefront_config = $current;
        $shop->save();

        return redirect()
            ->route('ecommerce.settings.index')
            ->with('success', 'Support client IA enregistré.');
    }

    // Plus de mise à jour de devise ici : la devise est gérée globalement via /settings/currencies
}
