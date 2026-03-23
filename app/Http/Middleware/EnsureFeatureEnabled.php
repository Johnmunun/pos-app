<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Application\Billing\Services\FeatureLimitService;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(
        private readonly FeatureLimitService $featureLimitService
    ) {
    }

    public function handle(Request $request, Closure $next, string $featureCode): Response
    {
        $tenantId = $request->user()?->tenant_id;
        $this->featureLimitService->assertFeatureEnabled(
            $tenantId !== null ? (string) $tenantId : null,
            $featureCode
        );

        return $next($request);
    }
}
