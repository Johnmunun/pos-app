<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    /**
     * Display currencies and exchange rates
     */
    public function index(): Response
    {
        $tenantId = Auth::user()->tenant_id;

        $currencies = Currency::where('tenant_id', $tenantId)
            ->get()
            ->map(fn($currency) => [
                'id' => $currency->id,
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'is_default' => $currency->is_default,
                'is_active' => $currency->is_active,
            ]);

        $exchangeRates = ExchangeRate::where('tenant_id', $tenantId)
            ->with(['fromCurrency', 'toCurrency'])
            ->get()
            ->map(fn($rate) => [
                'id' => $rate->id,
                'from_currency_id' => $rate->from_currency_id,
                'from_currency_code' => $rate->fromCurrency->code,
                'to_currency_id' => $rate->to_currency_id,
                'to_currency_code' => $rate->toCurrency->code,
                'rate' => $rate->rate,
                'effective_date' => $rate->effective_date->format('Y-m-d'),
            ]);

        return Inertia::render('Settings/Currencies', [
            'currencies' => $currencies,
            'exchangeRates' => $exchangeRates,
        ]);
    }

    /**
     * Store a new currency
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:3|unique:currencies,code,NULL,id,tenant_id,' . Auth::user()->tenant_id,
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'is_default' => 'boolean',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // If this is set as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            Currency::where('tenant_id', $tenantId)
                ->update(['is_default' => false]);
        }

        Currency::create([
            'tenant_id' => $tenantId,
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'symbol' => $validated['symbol'],
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Devise créée avec succès.');
    }

    /**
     * Update a currency
     */
    public function update(Request $request, Currency $currency)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($currency->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:3|unique:currencies,code,' . $currency->id . ',id,tenant_id,' . $tenantId,
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'is_default' => 'boolean',
        ]);

        // If this is set as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            Currency::where('tenant_id', $tenantId)
                ->where('id', '!=', $currency->id)
                ->update(['is_default' => false]);
        }

        $currency->update($validated);

        return redirect()->back()->with('success', 'Devise mise à jour avec succès.');
    }

    /**
     * Delete a currency
     */
    public function destroy(Currency $currency)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($currency->tenant_id !== $tenantId) {
            abort(403);
        }

        if ($currency->is_default) {
            return redirect()->back()->with('error', 'Impossible de supprimer la devise par défaut.');
        }

        $currency->delete();

        return redirect()->back()->with('success', 'Devise supprimée avec succès.');
    }

    /**
     * Store an exchange rate
     */
    public function storeExchangeRate(Request $request)
    {
        $validated = $request->validate([
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id',
            'rate' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        $tenantId = Auth::user()->tenant_id;

        ExchangeRate::create([
            'tenant_id' => $tenantId,
            'from_currency_id' => $validated['from_currency_id'],
            'to_currency_id' => $validated['to_currency_id'],
            'rate' => $validated['rate'],
            'effective_date' => $validated['effective_date'],
        ]);

        return redirect()->back()->with('success', 'Taux de change créé avec succès.');
    }

    /**
     * Update an exchange rate
     */
    public function updateExchangeRate(Request $request, ExchangeRate $exchangeRate)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($exchangeRate->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id',
            'rate' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        $exchangeRate->update($validated);

        return redirect()->back()->with('success', 'Taux de change mis à jour avec succès.');
    }

    /**
     * Delete an exchange rate
     */
    public function destroyExchangeRate(ExchangeRate $exchangeRate)
    {
        $tenantId = Auth::user()->tenant_id;

        if ($exchangeRate->tenant_id !== $tenantId) {
            abort(403);
        }

        $exchangeRate->delete();

        return redirect()->back()->with('success', 'Taux de change supprimé avec succès.');
    }
}
