<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

        // Si l'abonnement est expire: forcer le paiement avant toute utilisation.
        // On garde le compte connecté, mais on bloque l'accès (UX: aller directement payer).
        if ($this->isPlanExpired($user)) {
            $allowedExpiredRoutes = [
                'billing.onboarding.payment',
                'billing.payments.show',
                'api.billing.plans.public',
                'api.billing.payments.initiate',
                'api.billing.payments.latest',
                'api.billing.payments.status',
                'billing.payments.callback',
                'logout',
                'profile.edit',
                'profile.update',
                'profile.destroy',
            ];

            $currentRoute = $request->route()?->getName();
            if (!in_array($currentRoute, $allowedExpiredRoutes, true)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'Abonnement expiré. Paiement requis.',
                        'redirect_to' => route('billing.onboarding.payment'),
                    ], 423);
                }
                return Inertia::location(route('billing.onboarding.payment'));
            }
        }

        // Gate billing: obliger la souscription d'un plan actif avant utilisation.
        // On autorise uniquement les routes necessaires pour payer et sortir.
        if ($this->mustChoosePlan($user->tenant_id)) {
            $allowedPlanRoutes = [
                'billing.onboarding.payment',
                'billing.payments.show',
                'api.billing.plans.public',
                'api.billing.payments.initiate',
                'api.billing.payments.latest',
                'api.billing.payments.status',
                'billing.payments.callback',
                'logout',
                'profile.edit',
                'profile.update',
                'profile.destroy',
            ];

            $currentRoute = $request->route()?->getName();
            if (!in_array($currentRoute, $allowedPlanRoutes, true)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'Plan requis avant utilisation.',
                        'redirect_to' => route('billing.onboarding.payment'),
                    ], 423);
                }
                return Inertia::location(route('billing.onboarding.payment'));
            }
        }

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

        // Si l'utilisateur est pending
        if ($user->status === 'pending') {
            // Autoriser l'accès aux routes spécifiques pour users pending
            $allowedRoutes = [
                'pending',
                'billing.onboarding.payment',
                'billing.payments.show',
                'api.billing.plans.public',
                'api.billing.payments.initiate',
                'api.billing.payments.latest',
                'api.billing.payments.status',
                'billing.payments.callback',
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
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Compte en attente de validation.',
                    'redirect_to' => route('pending'),
                ], 423);
            }
            return Inertia::location(route('pending'));
        }

        // Cas par défaut (ne devrait pas arriver)
        return $next($request);
    }

    private function mustChoosePlan($tenantId): bool
    {
        if (!$tenantId) {
            return false;
        }

        if (!Schema::hasTable('tenant_plan_subscriptions')) {
            return false;
        }

        $subscription = DB::table('tenant_plan_subscriptions')
            ->where('tenant_id', (string) $tenantId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first(['id', 'ends_at', 'trial_ends_at']);

        if (!$subscription) {
            return true;
        }

        // Si une date de fin existe et est passee, considerer qu'il faut choisir un plan.
        if (!empty($subscription->ends_at) && now()->gt($subscription->ends_at)) {
            return true;
        }

        return false;
    }

    private function isPlanExpired($user): bool
    {
        if (!$user || !$user->tenant_id) {
            return false;
        }
        if (!Schema::hasTable('tenant_plan_subscriptions')) {
            return false;
        }

        $subscription = DB::table('tenant_plan_subscriptions')
            ->where('tenant_id', (string) $user->tenant_id)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first(['ends_at', 'trial_ends_at']);

        if (!$subscription) {
            return false;
        }

        if (!empty($subscription->ends_at) && now()->gt($subscription->ends_at)) {
            return true;
        }

        if (empty($subscription->ends_at) && !empty($subscription->trial_ends_at) && now()->gt($subscription->trial_ends_at)) {
            return true;
        }

        return false;
    }
}
