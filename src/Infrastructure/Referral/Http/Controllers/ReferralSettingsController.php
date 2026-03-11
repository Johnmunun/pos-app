<?php

namespace Src\Infrastructure\Referral\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Referral\Models\ReferralSettingModel;

class ReferralSettingsController
{
    private function getTenantId(Request $request): int
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }
        $tenantId = $user->tenant_id ?? $user->shop_id ?? null;
        if (!$tenantId) {
            abort(403, 'Tenant ID not found.');
        }

        return (int) $tenantId;
    }

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $settings = ReferralSettingModel::find($tenantId);

        return Inertia::render('Referral/Settings', [
            'settings' => $settings ? [
                'tenant_id' => $settings->tenant_id,
                'enabled' => $settings->enabled,
                'commission_type' => $settings->commission_type,
                'commission_value' => $settings->commission_value,
                'max_levels' => $settings->max_levels,
                'enabled_modules' => $settings->enabled_modules ?? [],
            ] : [
                'tenant_id' => $tenantId,
                'enabled' => false,
                'commission_type' => 'percentage',
                'commission_value' => 0,
                'max_levels' => 1,
                'enabled_modules' => [],
            ],
        ]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $data = $request->validate([
            'enabled' => 'boolean',
            'commission_type' => 'required|string|in:percentage,fixed',
            'commission_value' => 'required|numeric|min:0',
            'max_levels' => 'required|integer|min:1|max:5',
            'enabled_modules' => 'array',
            'enabled_modules.*' => 'string',
        ]);

        $settings = ReferralSettingModel::find($tenantId);
        if (!$settings) {
            $settings = new ReferralSettingModel();
            $settings->tenant_id = $tenantId;
        }

        $settings->enabled = (bool) ($data['enabled'] ?? false);
        $settings->commission_type = $data['commission_type'];
        $settings->commission_value = (float) $data['commission_value'];
        $settings->max_levels = (int) $data['max_levels'];
        $settings->enabled_modules = $data['enabled_modules'] ?? [];
        $settings->save();

        return redirect()
            ->route('referrals.settings.index')
            ->with('success', 'Configuration du parrainage mise à jour.');
    }
}

