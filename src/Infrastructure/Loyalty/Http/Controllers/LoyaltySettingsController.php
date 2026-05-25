<?php

namespace Src\Infrastructure\Loyalty\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Loyalty\Services\LoyaltyLimitService;
use Src\Application\Loyalty\Services\LoyaltyService;

class LoyaltySettingsController
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService,
        private readonly LoyaltyLimitService $limitService
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

        return Inertia::render('Loyalty/Settings', [
            'settings' => $this->loyaltyService->getSettings($tenantId),
            'stats' => $this->loyaltyService->getStats($tenantId),
        ]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $tenantId = $this->tenantId($request);

        if ($request->boolean('enabled')) {
            $this->limitService->assertLoyaltyEnabled((string) $tenantId);
        }

        $data = $request->validate([
            'enabled' => 'boolean',
            'earn_amount_per_point' => 'required|numeric|min:0.01',
            'points_per_earn_unit' => 'required|integer|min:1',
            'redeem_value_per_point' => 'required|numeric|min:0',
            'min_points_redeem' => 'required|integer|min:0',
            'max_discount_percent' => 'required|numeric|min:0|max:100',
            'points_expire_days' => 'nullable|integer|min:1',
            'tier_thresholds' => 'nullable|array',
            'tier_thresholds.silver' => 'nullable|integer|min:0',
            'tier_thresholds.gold' => 'nullable|integer|min:0',
            'tier_thresholds.vip' => 'nullable|integer|min:0',
        ]);

        $this->loyaltyService->saveSettings($tenantId, $data);

        return redirect()->route('loyalty.settings.index')->with('success', 'Paramètres fidélité enregistrés.');
    }
}
