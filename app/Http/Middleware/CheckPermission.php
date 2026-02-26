<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // ROOT users bypass toutes les vérifications de permission
        // Log pour debug
        Log::debug('CheckPermission middleware', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_type' => $user->type,
            'user_type_trimmed' => strtoupper(trim($user->type ?? '')),
            'is_root' => $user->isRoot(),
            'permission_required' => $permission,
            'route_name' => $request->route()?->getName(),
        ]);

        if ($user->isRoot()) {
            Log::debug('ROOT user detected, bypassing permission check');
            return $next($request);
        }

        $permissionCode = $permission ?? $request->route()?->getName();

        if (!$permissionCode) {
            abort(403, 'Permission not defined for this route.');
        }

        // Support multiple permissions separated by pipe (OR logic)
        $permissions = array_map('trim', explode('|', $permissionCode));
        $permissions = array_filter($permissions);

        $hasPermission = false;
        $userPermissions = $user->permissionCodes();

        Log::debug('CheckPermission - Permission check', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_type' => $user->type,
            'required_permissions' => $permissions,
            'user_permissions_count' => count($userPermissions),
        ]);

        foreach ($permissions as $perm) {
            if ($user->hasPermission($perm)) {
                $hasPermission = true;
                break;
            }
            // Accès module Quincaillerie : si on exige "module.hardware", accepter aussi tout utilisateur ayant une permission hardware.*
            if ($perm === 'module.hardware' && !empty($userPermissions)) {
                foreach ($userPermissions as $userPerm) {
                    if (is_string($userPerm) && str_starts_with($userPerm, 'hardware.')) {
                        $hasPermission = true;
                        break 2;
                    }
                }
            }
            // Idem pour module Pharmacy
            if ($perm === 'module.pharmacy' && !empty($userPermissions)) {
                foreach ($userPermissions as $userPerm) {
                    if (is_string($userPerm) && str_starts_with($userPerm, 'pharmacy.')) {
                        $hasPermission = true;
                        break 2;
                    }
                }
            }
        }

        if (!$hasPermission) {
            Log::warning('CheckPermission - Permission denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_type' => $user->type,
                'required_permissions' => $permissions,
                'user_permissions' => $userPermissions,
                'route_name' => $request->route()?->getName(),
            ]);
            abort(403, 'Permission denied.');
        }

        return $next($request);
    }
}

