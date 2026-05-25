<?php

namespace Src\Infrastructure\Loyalty\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Loyalty\Services\LoyaltyService;

class LoyaltyReportController
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService
    ) {
    }

    private function tenantId(Request $request): int
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            abort(403, 'Tenant introuvable.');
        }

        return (int) $user->tenant_id;
    }

    public function index(Request $request): Response
    {
        $tenantId = $this->tenantId($request);
        $from = $request->input('from');
        $to = $request->input('to');
        $module = $request->input('module');
        if ($module === '' || $module === 'all') {
            $module = null;
        }

        return Inertia::render('Loyalty/Reports/Index', [
            'report' => $this->loyaltyService->getReport($tenantId, $from, $to, $module),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'module' => $request->input('module', 'all'),
            ],
        ]);
    }
}
