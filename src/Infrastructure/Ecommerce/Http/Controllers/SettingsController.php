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
        $candidateId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        if ($candidateId !== null && $candidateId !== '') {
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

    // Plus de mise à jour de devise ici : la devise est gérée globalement via /settings/currencies
}
