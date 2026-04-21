<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        // Si pas d'utilisateur authentifié, continuer normalement
        if (!$user) {
            return $next($request);
        }

        // ROOT users bypass toutes les vérifications de statut
        // Log pour debug
        /** @var bool $isRoot */
        $isRoot = method_exists($user, 'isRoot')
            ? (bool) $user->{'isRoot'}()
            : (strtoupper(trim((string) ($user->type ?? ''))) === 'ROOT');

        Log::debug('EnsureUserIsActive middleware', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_type' => $user->type,
            'user_type_trimmed' => strtoupper(trim($user->type ?? '')),
            'user_status' => $user->status,
            'is_root' => $isRoot,
            'route_name' => $request->route()?->getName(),
        ]);

        if ($isRoot) {
            Log::debug('ROOT user detected, bypassing status check');
            return $next($request);
        }

        // Important UX: ne jamais bloquer toute l'application pour forcer le paiement.
        // Les limitations de plan sont gérées feature par feature.

        // Si l'utilisateur est activé, tout est OK
        if ($user->status === 'active') {
            return $next($request);
        }

        // Si l'utilisateur est bloqué ou suspendu, refuser l'accès
        if (in_array($user->status, ['blocked', 'suspended', 'inactive'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $status = (string) $user->status;
            $statusLabel = $status === 'blocked'
                ? 'bloque'
                : ($status === 'suspended' ? 'suspendu' : 'desactive');

            return redirect()->route('login')->withErrors([
                'email' => 'Votre compte a été ' . $statusLabel . '. Veuillez contacter l\'administrateur.',
            ]);
        }

        // Compatibilité historique: ne plus bloquer les comptes pending.
        // On les active automatiquement pour respecter le flux Trial.
        if ($user->status === 'pending') {
            try {
                $user->status = 'active';
                if (Schema::hasColumn('users', 'is_active')) {
                    $user->is_active = true;
                }
                $user->save();
            } catch (\Throwable $e) {
                Log::warning('Failed to auto-activate pending user in middleware', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
            return $next($request);
        }

        // Cas par défaut (ne devrait pas arriver)
        return $next($request);
    }

}
