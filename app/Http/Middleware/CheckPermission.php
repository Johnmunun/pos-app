<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        $permissionCode = $permission ?? $request->route()?->getName();

        if (!$permissionCode) {
            abort(403, 'Permission not defined for this route.');
        }

        if (!$user->hasPermission($permissionCode)) {
            abort(403, 'Permission denied.');
        }

        return $next($request);
    }
}

