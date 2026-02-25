<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gère le contexte dépôt (sélection) et la liste des dépôts du tenant.
 */
class DepotController extends Controller
{
    /**
     * Liste des dépôts du tenant (page gestion).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            abort(403, 'Non authentifié ou sans tenant.');
        }

        $depots = Depot::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'address', 'city', 'is_active', 'created_at'])
            ->map(fn (Depot $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'address' => $d->address,
                'city' => $d->city,
                'is_active' => $d->is_active,
                'created_at' => $d->created_at?->format('d/m/Y'),
            ]);

        return Inertia::render('Pharmacy/Depots/Index', [
            'depots' => $depots,
        ]);
    }

    /**
     * Créer un dépôt.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            abort(403, 'Non authentifié ou sans tenant.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
        ]);

        $codeExists = Depot::where('tenant_id', $user->tenant_id)
            ->where('code', $validated['code'])
            ->exists();
        if ($codeExists) {
            return redirect()->back()->withErrors(['code' => 'Ce code de dépôt existe déjà.']);
        }

        Depot::create([
            'tenant_id' => $user->tenant_id,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('pharmacy.depots.index')->with('success', 'Dépôt créé avec succès.');
    }

    /**
     * Changer le dépôt actif (session).
     */
    public function switch(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->tenant_id) {
            return back()->withErrors(['error' => 'Non authentifié ou sans tenant.']);
        }

        $depotId = $request->input('depot_id');
        if ($depotId === null || $depotId === '' || $depotId === false) {
            $request->session()->forget('current_depot_id');
            return back()->with('success', 'Dépôt désélectionné.');
        }

        $depotId = (int) $depotId;
        $depot = Depot::where('id', $depotId)
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->first();

        if (!$depot) {
            return back()->withErrors(['error' => 'Dépôt non trouvé ou inaccessible.']);
        }

        $request->session()->put('current_depot_id', $depot->id);

        return back()->with('success', 'Dépôt sélectionné : ' . $depot->name);
    }
}
