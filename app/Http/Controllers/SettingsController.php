<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display company settings
     */
    public function company(): Response
    {
        $tenant = Auth::user()->tenant;
        
        return Inertia::render('Settings/Company', [
            'tenant' => [
                'name' => $tenant->name ?? '',
                'email' => $tenant->email ?? '',
                'address' => $tenant->address ?? '',
                'phone' => $tenant->phone ?? '',
                'idnat' => $tenant->idnat ?? '',
                'rccm' => $tenant->rccm ?? '',
            ],
        ]);
    }

    /**
     * Update company settings
     */
    public function updateCompany(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'idnat' => 'nullable|string|max:50',
            'rccm' => 'nullable|string|max:50',
        ]);

        $tenant = Auth::user()->tenant;
        $tenant->update($validated);

        return redirect()->back()->with('success', 'Informations entreprise mises à jour avec succès.');
    }
}
