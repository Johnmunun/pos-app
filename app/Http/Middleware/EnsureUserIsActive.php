<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * Vérifie que l'utilisateur est activé (status = active).
     * Les utilisateurs pending ne peuvent accéder qu'à certaines pages.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Si pas d'utilisateur authentifié, continuer normalement
        if (!$user) {
            return $next($request);
        }

        // ROOT users bypass toutes les vérifications de statut
        // Log pour debug
        Log::debug('EnsureUserIsActive middleware', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_type' => $user->type,
            'user_type_trimmed' => strtoupper(trim($user->type ?? '')),
            'user_status' => $user->status,
            'is_root' => $user->isRoot(),
            'route_name' => $request->route()?->getName(),
        ]);

        if ($user->isRoot()) {
            Log::debug('ROOT user detected, bypassing status check');
            return $next($request);
        }

        // Si l'utilisateur est activé, tout est OK
        if ($user->status === 'active') {
            return $next($request);
        }

        // Si l'utilisateur est bloqué ou suspendu, refuser l'accès
        if (in_array($user->status, ['blocked', 'suspended'])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')->withErrors([
                'email' => 'Votre compte a été ' . ($user->status === 'blocked' ? 'bloqué' : 'suspendu') . '. Veuillez contacter l\'administrateur.',
            ]);
        }

        // Si l'utilisateur est pending
        if ($user->status === 'pending') {
            // Autoriser l'accès aux routes spécifiques pour users pending
            $allowedRoutes = [
                'pending',
                'profile.edit',
                'profile.update',
                'profile.destroy',
                'logout',
                'password.update',
                'onboarding.step1',
                'onboarding.step2',
                'onboarding.step3',
                'onboarding.step4',
                'onboarding.complete',
            ];

            // Vérifier si la route actuelle est autorisée
            if (in_array($request->route()->getName(), $allowedRoutes)) {
                return $next($request);
            }

            // Sinon rediriger vers la page pending
            return Inertia::location(route('pending'));
        }

        // Cas par défaut (ne devrait pas arriver)
        return $next($request);
    }
}
