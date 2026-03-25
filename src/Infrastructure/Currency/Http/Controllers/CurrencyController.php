<?php

namespace Src\Infrastructure\Currency\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Currency\DTO\CreateCurrencyDTO;
use Src\Application\Currency\DTO\UpdateCurrencyDTO;
use Src\Application\Currency\UseCases\CreateCurrencyUseCase;
use Src\Application\Currency\UseCases\DeleteCurrencyUseCase;
use Src\Application\Currency\UseCases\GetCurrenciesUseCase;
use Src\Application\Currency\UseCases\UpdateCurrencyUseCase;
use App\Models\ExchangeRate;

/**
 * Controller: CurrencyController
 * 
 * Gère les devises selon l'architecture DDD
 */
class CurrencyController
{
    private function isGlobalScope(Request $request): bool
    {
        $user = $request->user();
        $isRoot = $user && method_exists($user, 'isRoot') && $user->isRoot();
        if (!$isRoot) {
            return false;
        }

        return strtolower((string) $request->query('scope', $request->input('scope', ''))) === 'global';
    }

    private function resolveTenantId(Request $request): int
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        // Si ROOT, autoriser de passer un tenant_id explicitement (query/body)
        if (method_exists($user, 'isRoot') && $user->isRoot()) {
            if ($this->isGlobalScope($request)) {
                abort(422, 'Mode global actif: aucun tenant requis.');
            }
            $requestedTenantId = (int) ($request->input('tenant_id') ?? $request->query('tenant_id') ?? 0);
            if ($requestedTenantId > 0) {
                $exists = \Illuminate\Support\Facades\DB::table('tenants')
                    ->where('id', $requestedTenantId)
                    ->exists();
                if ($exists) {
                    return $requestedTenantId;
                }
                abort(422, 'Tenant invalide.');
            }

            // IMPORTANT: ne plus rediriger vers /admin/select-tenant.
            // ROOT doit choisir un tenant via le dropdown sur /settings/currencies.
            abort(422, 'Selectionnez un tenant dans la liste avant de gerer les devises.');
        }

        $tenantId = (int) ($user->tenant_id ?? 0);
        if ($tenantId > 0) {
            return $tenantId;
        }

        abort(422, 'Aucun tenant associe a cet utilisateur.');
    }

    public function __construct(
        private GetCurrenciesUseCase $getCurrenciesUseCase,
        private CreateCurrencyUseCase $createCurrencyUseCase,
        private UpdateCurrencyUseCase $updateCurrencyUseCase,
        private DeleteCurrencyUseCase $deleteCurrencyUseCase
    ) {}

    /**
     * Affiche la liste des devises et taux de change
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $globalScope = $this->isGlobalScope($request);

        // Si ROOT sans tenant selectionne, afficher la page avec dropdown + listes vides.
        if (method_exists($user, 'isRoot') && $user->isRoot()) {
            $requestedTenantId = (int) ($request->query('tenant_id') ?? 0);
            if (!$globalScope && $requestedTenantId <= 0) {
                return Inertia::render('Settings/Currencies', [
                    'currencies' => [],
                    'exchangeRates' => [],
                    'tenant' => ['id' => null],
                    'scope' => 'tenant',
                    'tenants' => \App\Models\Tenant::query()
                        ->orderBy('name')
                        ->limit(300)
                        ->get(['id', 'name'])
                        ->map(fn ($t) => ['id' => (int) $t->id, 'name' => (string) $t->name])
                        ->toArray(),
                    'isRoot' => true,
                ]);
            }
        }

        $tenantId = $globalScope ? 0 : $this->resolveTenantId($request);
        $isRoot = method_exists($user, 'isRoot') && $user->isRoot();

        if ($isRoot) {
            if ($globalScope) {
                // ROOT (GLOBAL): uniquement les devises/taux globaux (tenant_id NULL)
                $currenciesData = \App\Models\Currency::query()
                    ->whereNull('tenant_id')
                    ->select(['id', 'tenant_id', 'code', 'name', 'symbol', 'is_default', 'is_active'])
                    ->orderByDesc('is_default')
                    ->orderBy('code')
                    ->get()
                    ->map(fn ($c) => [
                        'id' => (int) $c->id,
                        'tenant_id' => null,
                        'code' => (string) $c->code,
                        'name' => (string) $c->name,
                        'symbol' => (string) $c->symbol,
                        'is_default' => (bool) $c->is_default,
                        'is_active' => (bool) $c->is_active,
                    ])
                    ->values()
                    ->toArray();

                $exchangeRates = ExchangeRate::query()
                    ->whereNull('tenant_id')
                    ->with(['fromCurrency', 'toCurrency'])
                    ->orderByDesc('effective_date')
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn ($rate) => [
                        'id' => (int) $rate->id,
                        'tenant_id' => null,
                        'from_currency_id' => (int) $rate->from_currency_id,
                        'to_currency_id' => (int) $rate->to_currency_id,
                        'from_currency_code' => (string) ($rate->fromCurrency->code ?? ''),
                        'to_currency_code' => (string) ($rate->toCurrency->code ?? ''),
                        'rate' => $rate->rate,
                        'effective_date' => $rate->effective_date,
                    ])
                    ->values()
                    ->toArray();
            } else {
                // ROOT (TENANT): vue globale (tous tenants), mais les formulaires utilisent toujours $tenantId pour écrire.
                $currenciesData = \App\Models\Currency::query()
                    ->select(['id', 'tenant_id', 'code', 'name', 'symbol', 'is_default', 'is_active'])
                    ->orderByDesc('is_default')
                    ->orderBy('tenant_id')
                    ->orderBy('code')
                    ->get()
                    ->map(fn ($c) => [
                        'id' => (int) $c->id,
                        'tenant_id' => $c->tenant_id !== null ? (int) $c->tenant_id : null,
                        'code' => (string) $c->code,
                        'name' => (string) $c->name,
                        'symbol' => (string) $c->symbol,
                        'is_default' => (bool) $c->is_default,
                        'is_active' => (bool) $c->is_active,
                    ])
                    ->values()
                    ->toArray();

                $exchangeRates = ExchangeRate::query()
                    ->with(['fromCurrency', 'toCurrency'])
                    ->orderByDesc('effective_date')
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn ($rate) => [
                        'id' => (int) $rate->id,
                        'tenant_id' => $rate->tenant_id !== null ? (int) $rate->tenant_id : null,
                        'from_currency_id' => (int) $rate->from_currency_id,
                        'to_currency_id' => (int) $rate->to_currency_id,
                        'from_currency_code' => (string) ($rate->fromCurrency->code ?? ''),
                        'to_currency_code' => (string) ($rate->toCurrency->code ?? ''),
                        'rate' => $rate->rate,
                        'effective_date' => $rate->effective_date,
                    ])
                    ->values()
                    ->toArray();
            }
        } else {
            // Tenant normal: vue filtrée (tenant courant)
            $currencies = $this->getCurrenciesUseCase->execute($tenantId);
            $currenciesData = array_map(function ($currency) {
                return [
                    'id' => $currency->getId(),
                    'tenant_id' => $currency->getTenantId(),
                    'code' => $currency->getCode()->getValue(),
                    'name' => $currency->getName()->getValue(),
                    'symbol' => $currency->getSymbol()->getValue(),
                    'is_default' => $currency->isDefault(),
                    'is_active' => $currency->isActive(),
                ];
            }, $currencies);

            $exchangeRates = ExchangeRate::whereHas('fromCurrency', function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->with(['fromCurrency', 'toCurrency'])
                ->get()
                ->map(function ($rate) {
                    return [
                        'id' => (int) $rate->id,
                        'tenant_id' => $rate->tenant_id !== null ? (int) $rate->tenant_id : null,
                        'from_currency_id' => (int) $rate->from_currency_id,
                        'to_currency_id' => (int) $rate->to_currency_id,
                        'from_currency_code' => (string) ($rate->fromCurrency->code ?? ''),
                        'to_currency_code' => (string) ($rate->toCurrency->code ?? ''),
                        'rate' => $rate->rate,
                        'effective_date' => $rate->effective_date,
                    ];
                })
                ->values()
                ->toArray();
        }

        return Inertia::render('Settings/Currencies', [
            'currencies' => $currenciesData,
            'exchangeRates' => $exchangeRates,
            'tenant' => [
                'id' => $tenantId,
            ],
            'scope' => $globalScope ? 'global' : 'tenant',
            'tenants' => (method_exists($user, 'isRoot') && $user->isRoot())
                ? \App\Models\Tenant::query()
                    ->orderBy('name')
                    ->limit(300)
                    ->get(['id', 'name'])
                    ->map(fn ($t) => ['id' => (int) $t->id, 'name' => (string) $t->name])
                    ->toArray()
                : [],
            'isRoot' => (bool) (method_exists($user, 'isRoot') && $user->isRoot()),
        ]);
    }

    /**
     * Crée une nouvelle devise
     */
    public function store(Request $request)
    {
        if ($this->isGlobalScope($request)) {
            $user = $request->user();
            if (!$user || !method_exists($user, 'isRoot') || !$user->isRoot()) {
                abort(403, 'Accès non autorisé');
            }

            $validated = $request->validate([
                'code' => 'required|string|size:3',
                'name' => 'required|string|max:255',
                'symbol' => 'required|string|max:10',
                'is_default' => 'boolean',
            ]);

            $code = strtoupper($validated['code']);
            $isDefault = (bool) ($validated['is_default'] ?? false);

            if ($isDefault) {
                \App\Models\Currency::query()->whereNull('tenant_id')->update(['is_default' => false]);
            }

            \App\Models\Currency::query()->updateOrCreate(
                ['tenant_id' => null, 'code' => $code],
                [
                    'name' => $validated['name'],
                    'symbol' => $validated['symbol'],
                    'is_default' => $isDefault,
                    'is_active' => true,
                ]
            );

            return redirect()->route('settings.currencies', ['scope' => 'global'])
                ->with('success', 'Devise globale créée avec succès');
        }

        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'code' => 'required|string|size:3',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'is_default' => 'boolean',
        ]);

        try {
            $dto = new CreateCurrencyDTO(
                tenantId: $tenantId,
                code: strtoupper($validated['code']),
                name: $validated['name'],
                symbol: $validated['symbol'],
                isDefault: $validated['is_default'] ?? false
            );

            $this->createCurrencyUseCase->execute($dto);

            return redirect()->route('settings.currencies')
                ->with('success', 'Devise créée avec succès');
        } catch (\DomainException $e) {
            return redirect()->back()
                ->withErrors(['message' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Met à jour une devise
     */
    public function update(Request $request, int $currency)
    {
        if ($this->isGlobalScope($request)) {
            $user = $request->user();
            if (!$user || !method_exists($user, 'isRoot') || !$user->isRoot()) {
                abort(403, 'Accès non autorisé');
            }

            $c = \App\Models\Currency::query()->whereNull('tenant_id')->where('id', $currency)->firstOrFail();

            $validated = $request->validate([
                'code' => 'sometimes|string|size:3',
                'name' => 'sometimes|string|max:255',
                'symbol' => 'sometimes|string|max:10',
                'is_default' => 'sometimes|boolean',
            ]);

            if (array_key_exists('is_default', $validated) && (bool) $validated['is_default']) {
                \App\Models\Currency::query()->whereNull('tenant_id')->update(['is_default' => false]);
            }

            $c->update([
                'code' => isset($validated['code']) ? strtoupper($validated['code']) : $c->code,
                'name' => $validated['name'] ?? $c->name,
                'symbol' => $validated['symbol'] ?? $c->symbol,
                'is_default' => array_key_exists('is_default', $validated) ? (bool) $validated['is_default'] : $c->is_default,
            ]);

            return redirect()->route('settings.currencies', ['scope' => 'global'])
                ->with('success', 'Devise globale mise à jour avec succès');
        }

        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'code' => 'sometimes|string|size:3',
            'name' => 'sometimes|string|max:255',
            'symbol' => 'sometimes|string|max:10',
            'is_default' => 'sometimes|boolean',
        ]);

        try {
            $dto = new UpdateCurrencyDTO(
                id: $currency,
                code: isset($validated['code']) ? strtoupper($validated['code']) : null,
                name: $validated['name'] ?? null,
                symbol: $validated['symbol'] ?? null,
                isDefault: $validated['is_default'] ?? null
            );

            $this->updateCurrencyUseCase->execute($dto);

            return redirect()->route('settings.currencies')
                ->with('success', 'Devise mise à jour avec succès');
        } catch (\DomainException $e) {
            return redirect()->back()
                ->withErrors(['message' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Supprime une devise
     */
    public function destroy(int $currency)
    {
        $request = request();
        if ($this->isGlobalScope($request)) {
            $user = $request->user();
            if (!$user || !method_exists($user, 'isRoot') || !$user->isRoot()) {
                abort(403, 'Accès non autorisé');
            }

            \App\Models\Currency::query()->whereNull('tenant_id')->where('id', $currency)->delete();

            return redirect()->route('settings.currencies', ['scope' => 'global'])
                ->with('success', 'Devise globale supprimée avec succès');
        }

        try {
            $this->deleteCurrencyUseCase->execute($currency);

            return redirect()->route('settings.currencies')
                ->with('success', 'Devise supprimée avec succès');
        } catch (\DomainException $e) {
            return redirect()->back()
                ->withErrors(['message' => $e->getMessage()]);
        }
    }

    // Méthodes pour ExchangeRate (temporairement, à refactoriser plus tard)
    public function storeExchangeRate(Request $request)
    {
        if ($this->isGlobalScope($request)) {
            $user = $request->user();
            if (!$user || !method_exists($user, 'isRoot') || !$user->isRoot()) {
                abort(403, 'Accès non autorisé');
            }

            $validated = $request->validate([
                'from_currency_id' => 'required|exists:currencies,id',
                'to_currency_id' => 'required|exists:currencies,id|different:from_currency_id',
                'rate' => 'required|numeric|min:0',
                'effective_date' => 'required|date',
            ]);

            $fromCurrency = \App\Models\Currency::findOrFail($validated['from_currency_id']);
            $toCurrency = \App\Models\Currency::findOrFail($validated['to_currency_id']);
            if ($fromCurrency->tenant_id !== null || $toCurrency->tenant_id !== null) {
                abort(403, 'En mode global, choisissez des devises globales.');
            }

            ExchangeRate::create([
                'tenant_id' => null,
                ...$validated,
            ]);

            return redirect()->route('settings.currencies', ['scope' => 'global'])
                ->with('success', 'Taux de change global créé avec succès');
        }

        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id|different:from_currency_id',
            'rate' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        // Vérifier que les devises appartiennent au tenant
        $fromCurrency = \App\Models\Currency::findOrFail($validated['from_currency_id']);
        $toCurrency = \App\Models\Currency::findOrFail($validated['to_currency_id']);

        if ($fromCurrency->tenant_id !== $tenantId || $toCurrency->tenant_id !== $tenantId) {
            abort(403, 'Accès non autorisé');
        }

        // Forcer le tenant_id sur le taux de change créé
        ExchangeRate::create([
            'tenant_id' => $tenantId,
            ...$validated,
        ]);

        return redirect()->route('settings.currencies')
            ->with('success', 'Taux de change créé avec succès');
    }

    public function updateExchangeRate(Request $request, ExchangeRate $exchangeRate)
    {
        if ($this->isGlobalScope($request)) {
            $user = $request->user();
            if (!$user || !method_exists($user, 'isRoot') || !$user->isRoot()) {
                abort(403, 'Accès non autorisé');
            }

            if ($exchangeRate->tenant_id !== null) {
                abort(403, 'Ce taux n’est pas global.');
            }

            $validated = $request->validate([
                'from_currency_id' => 'required|exists:currencies,id',
                'to_currency_id' => 'required|exists:currencies,id|different:from_currency_id',
                'rate' => 'required|numeric|min:0',
                'effective_date' => 'required|date',
            ]);

            $fromCurrency = \App\Models\Currency::findOrFail($validated['from_currency_id']);
            $toCurrency = \App\Models\Currency::findOrFail($validated['to_currency_id']);
            if ($fromCurrency->tenant_id !== null || $toCurrency->tenant_id !== null) {
                abort(403, 'En mode global, choisissez des devises globales.');
            }

            $exchangeRate->update([
                'tenant_id' => null,
                ...$validated,
            ]);

            return redirect()->route('settings.currencies', ['scope' => 'global'])
                ->with('success', 'Taux de change global mis à jour avec succès');
        }

        $tenantId = $this->resolveTenantId($request);

        $fromCurrency = $exchangeRate->fromCurrency;
        if ($fromCurrency && $fromCurrency->tenant_id !== $tenantId) {
            abort(403, 'Accès non autorisé');
        }

        $validated = $request->validate([
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id|different:from_currency_id',
            'rate' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        $toCurrency = \App\Models\Currency::findOrFail($validated['to_currency_id']);
        if ($toCurrency->tenant_id !== $tenantId) {
            abort(403, 'Accès non autorisé');
        }

        // Mettre à jour les champs + s'assurer que tenant_id est bien positionné
        $exchangeRate->update([
            'tenant_id' => $tenantId,
            ...$validated,
        ]);

        return redirect()->route('settings.currencies')
            ->with('success', 'Taux de change mis à jour avec succès');
    }

    public function destroyExchangeRate(ExchangeRate $exchangeRate)
    {
        $request = request();
        if ($this->isGlobalScope($request)) {
            $user = $request->user();
            if (!$user || !method_exists($user, 'isRoot') || !$user->isRoot()) {
                abort(403, 'Accès non autorisé');
            }
            if ($exchangeRate->tenant_id !== null) {
                abort(403, 'Ce taux n’est pas global.');
            }
            $exchangeRate->delete();

            return redirect()->route('settings.currencies', ['scope' => 'global'])
                ->with('success', 'Taux de change global supprimé avec succès');
        }

        $tenantId = $this->resolveTenantId($request);

        $fromCurrency = $exchangeRate->fromCurrency;
        if ($fromCurrency && $fromCurrency->tenant_id !== $tenantId) {
            abort(403, 'Accès non autorisé');
        }

        $exchangeRate->delete();

        return redirect()->route('settings.currencies')
            ->with('success', 'Taux de change supprimé avec succès');
    }
}
