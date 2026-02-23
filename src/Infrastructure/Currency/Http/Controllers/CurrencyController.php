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
        $tenantId = $user->tenant_id;

        // Récupérer les devises via Use Case
        $currencies = $this->getCurrenciesUseCase->execute($tenantId);

        // Transformer en format pour Inertia
        $currenciesData = array_map(function ($currency) {
            return [
                'id' => $currency->getId(),
                'code' => $currency->getCode()->getValue(),
                'name' => $currency->getName()->getValue(),
                'symbol' => $currency->getSymbol()->getValue(),
                'is_default' => $currency->isDefault(),
                'is_active' => $currency->isActive(),
            ];
        }, $currencies);

        // Récupérer les taux de change (temporairement avec Eloquent direct)
        $exchangeRates = ExchangeRate::whereHas('fromCurrency', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->with(['fromCurrency', 'toCurrency'])
            ->get()
            ->map(function ($rate) {
                return [
                    'id' => $rate->id,
                    'from_currency_id' => $rate->from_currency_id,
                    'to_currency_id' => $rate->to_currency_id,
                    'from_currency_code' => $rate->fromCurrency->code ?? '',
                    'to_currency_code' => $rate->toCurrency->code ?? '',
                    'rate' => $rate->rate,
                    'effective_date' => $rate->effective_date,
                ];
            });

        return Inertia::render('Settings/Currencies', [
            'currencies' => $currenciesData,
            'exchangeRates' => $exchangeRates,
        ]);
    }

    /**
     * Crée une nouvelle devise
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $tenantId = $user->tenant_id;

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
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $tenantId = $user->tenant_id;

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
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $tenantId = $user->tenant_id;

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

        ExchangeRate::create($validated);

        return redirect()->route('settings.currencies')
            ->with('success', 'Taux de change créé avec succès');
    }

    public function updateExchangeRate(Request $request, ExchangeRate $exchangeRate)
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $tenantId = $user->tenant_id;

        $fromCurrency = $exchangeRate->fromCurrency;
        if ($fromCurrency->tenant_id !== $tenantId) {
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

        $exchangeRate->update($validated);

        return redirect()->route('settings.currencies')
            ->with('success', 'Taux de change mis à jour avec succès');
    }

    public function destroyExchangeRate(ExchangeRate $exchangeRate)
    {
        $user = request()->user();
        $tenantId = $user->tenant_id;

        $fromCurrency = $exchangeRate->fromCurrency;
        if ($fromCurrency->tenant_id !== $tenantId) {
            abort(403, 'Accès non autorisé');
        }

        $exchangeRate->delete();

        return redirect()->route('settings.currencies')
            ->with('success', 'Taux de change supprimé avec succès');
    }
}
