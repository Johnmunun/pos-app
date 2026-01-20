<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Générer un slug unique
        $baseSlug = Str::slug($request->company_name);
        $slug = $baseSlug;
        $counter = 1;
        
        // Vérifier l'unicité du slug
        while (DB::table('tenants')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Créer un tenant pour cette entreprise
        $tenant = DB::table('tenants')->insertGetId([
            'code' => strtoupper(Str::random(8)), // Code unique aléatoire
            'name' => $request->company_name,
            'slug' => $slug,
            'email' => $request->email, // Utiliser l'email de l'utilisateur comme email du tenant
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Extraire prénom et nom
        $nameParts = explode(' ', $request->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // Créer l'utilisateur avec le tenant_id
        $user = User::create([
            'name' => $request->name, // Nom complet (requis par la table)
            'tenant_id' => $tenant,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'TENANT_ADMIN', // Premier utilisateur = admin du tenant
            'is_active' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
