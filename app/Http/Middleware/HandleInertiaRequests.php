<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        
        // Récupérer les permissions de l'utilisateur
        $permissions = [];
        if ($user) {
            try {
                $permissions = $user->permissionCodes();
                // Log pour debug (à retirer en production)
                \Log::debug('User permissions', [
                    'user_id' => $user->id,
                    'user_type' => $user->type,
                    'permissions_count' => count($permissions),
                    'permissions' => $permissions,
                ]);
            } catch (\Exception $e) {
                \Log::error('Error getting user permissions', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $permissions = [];
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $permissions,
                'isImpersonating' => $request->session()->get('impersonate.impersonating', false),
                'originalUserId' => $request->session()->get('impersonate.original_user_id'),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
            ],
        ];
    }

    /**
     * Handle Inertia responses.
     */
    public function rootView(Request $request): string
    {
        return parent::rootView($request);
    }

    /**
     * Set the root template that's loaded on the first Inertia page visit.
     */
    public function rootTemplate(Request $request): string
    {
        return parent::rootTemplate($request);
    }
}
