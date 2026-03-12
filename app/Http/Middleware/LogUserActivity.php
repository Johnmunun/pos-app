<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Infrastructure\Logs\Models\UserActivityModel;

class LogUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $user = $request->user();

            if ($user && $request->method() !== 'GET') {
                UserActivityModel::create([
                    'user_id' => $user->id,
                    'action' => $request->method(),
                    'module' => $this->detectModuleFromPath($request->path()),
                    'route' => $request->path(),
                    'ip_address' => $request->ip(),
                    'changes' => [],
                ]);
            }
        } catch (\Throwable $e) {
            // Ne jamais casser la requête si le log échoue
        }

        return $response;
    }

    private function detectModuleFromPath(string $path): ?string
    {
        if (str_starts_with($path, 'pharmacy')) {
            return 'pharmacy';
        }
        if (str_starts_with($path, 'hardware')) {
            return 'hardware';
        }
        if (str_starts_with($path, 'commerce')) {
            return 'commerce';
        }
        if (str_starts_with($path, 'ecommerce')) {
            return 'ecommerce';
        }
        if (str_starts_with($path, 'finance')) {
            return 'finance';
        }
        if (str_starts_with($path, 'admin')) {
            return 'admin';
        }

        return null;
    }
}

