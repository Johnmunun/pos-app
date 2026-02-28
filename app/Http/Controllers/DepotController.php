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
            ->get(['id', 'name', 'code', 'address', 'city', 'postal_code', 'country', 'phone', 'email', 'is_active', 'created_at'])
            ->map(fn (Depot $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'address' => $d->address,
                'city' => $d->city,
                'postal_code' => $d->postal_code,
                'country' => $d->country,
                'phone' => $d->phone,
                'email' => $d->email,
                'is_active' => $d->is_active,
                'created_at' => $d->created_at?->format('d/m/Y'),
            ]);

        $module = $this->getModule();
        $viewPath = $module === 'Hardware' ? 'Hardware/Depots/Index' : 'Pharmacy/Depots/Index';
        
        return Inertia::render($viewPath, [
            'depots' => $depots,
        ]);
    }

    /**
     * Détecte le module (Pharmacy ou Hardware) depuis la route
     */
    private function getModule(): string
    {
        $routeName = request()->route()?->getName();
        
        // Vérifier le nom de la route d'abord (le plus fiable)
        if ($routeName && str_starts_with($routeName, 'hardware.')) {
            return 'Hardware';
        }
        
        // Vérifier l'URL en fallback
        $url = request()->url();
        if (str_contains($url, '/hardware/')) {
            return 'Hardware';
        }
        
        // Vérifier le préfixe de la route en dernier recours
        $prefix = request()->route()?->getPrefix();
        if ($prefix === 'hardware') {
            return 'Hardware';
        }
        
        // Par défaut, Pharmacy
        return 'Pharmacy';
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

        $module = $this->getModule();
        $permission = $module === 'Hardware' ? 'hardware.warehouse.create' : 'pharmacy.seller.create';
        if (!$user->hasPermission($permission) && $user->type !== 'ROOT') {
            abort(403, 'Permission refusée.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
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
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? 'CM',
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => true,
        ]);

        $routeName = $module === 'Hardware' ? 'hardware.depots.index' : 'pharmacy.depots.index';
        return redirect()->route($routeName)->with('success', 'Dépôt créé avec succès.');
    }

    /**
     * Mettre à jour un dépôt.
     */
    public function update(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            abort(403, 'Non authentifié ou sans tenant.');
        }

        $module = $this->getModule();
        $permission = $module === 'Hardware' ? 'hardware.warehouse.update' : 'pharmacy.seller.edit';
        if (!$user->hasPermission($permission) && $user->type !== 'ROOT') {
            abort(403, 'Permission refusée.');
        }

        $depot = Depot::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (!$depot) {
            abort(404, 'Dépôt non trouvé.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        // Vérifier que le code n'existe pas pour un autre dépôt
        $codeExists = Depot::where('tenant_id', $user->tenant_id)
            ->where('code', $validated['code'])
            ->where('id', '!=', $id)
            ->exists();
        if ($codeExists) {
            return redirect()->back()->withErrors(['code' => 'Ce code de dépôt existe déjà.']);
        }

        $depot->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? 'CM',
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
        ]);

        $routeName = $module === 'Hardware' ? 'hardware.depots.index' : 'pharmacy.depots.index';
        return redirect()->route($routeName)->with('success', 'Dépôt mis à jour avec succès.');
    }

    /**
     * Activer un dépôt.
     */
    public function activate(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            abort(403, 'Non authentifié ou sans tenant.');
        }

        $module = $this->getModule();
        $permission = $module === 'Hardware' ? 'hardware.warehouse.activate' : 'pharmacy.seller.edit';
        if (!$user->hasPermission($permission) && $user->type !== 'ROOT') {
            abort(403, 'Permission refusée.');
        }

        $depot = Depot::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (!$depot) {
            abort(404, 'Dépôt non trouvé.');
        }

        $depot->update(['is_active' => true]);

        $routeName = $module === 'Hardware' ? 'hardware.depots.index' : 'pharmacy.depots.index';
        return redirect()->route($routeName)->with('success', 'Dépôt activé avec succès.');
    }

    /**
     * Désactiver un dépôt.
     */
    public function deactivate(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            abort(403, 'Non authentifié ou sans tenant.');
        }

        $module = $this->getModule();
        $permission = $module === 'Hardware' ? 'hardware.warehouse.deactivate' : 'pharmacy.seller.edit';
        if (!$user->hasPermission($permission) && $user->type !== 'ROOT') {
            abort(403, 'Permission refusée.');
        }

        $depot = Depot::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (!$depot) {
            abort(404, 'Dépôt non trouvé.');
        }

        $depot->update(['is_active' => false]);

        $routeName = $module === 'Hardware' ? 'hardware.depots.index' : 'pharmacy.depots.index';
        return redirect()->route($routeName)->with('success', 'Dépôt désactivé avec succès.');
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
