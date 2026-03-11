<?php

namespace Src\Infrastructure\Referral\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Referral\Models\ReferralAccountModel;
use Src\Infrastructure\Referral\Models\ReferralCommissionModel;

class ReferralDashboardController
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
        $user = $request->user();
        $tenantId = $this->getTenantId($request);

        // Créer le compte referral si inexistant
        $account = ReferralAccountModel::firstOrCreate(
            ['tenant_id' => $tenantId, 'user_id' => $user->id],
            ['code' => strtoupper(\Illuminate\Support\Str::random(8))]
        );

        $referralLink = route('register') . '?ref=' . $account->code;

        // Filleuls directs : comptes dont parent_id = ce compte
        $directChildren = ReferralAccountModel::where('tenant_id', $tenantId)
            ->where('parent_id', $account->id)
            ->get();

        $directChildrenData = $directChildren->map(function (ReferralAccountModel $acc) {
            return [
                'id' => (string) $acc->id,
                'user_id' => (int) $acc->user_id,
                'code' => $acc->code,
                'created_at' => optional($acc->created_at)->format('Y-m-d H:i'),
            ];
        })->toArray();

        // Commissions de l'utilisateur (tous niveaux)
        $commissions = ReferralCommissionModel::where('tenant_id', $tenantId)
            ->where('referrer_account_id', $account->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $totalCommissions = (float) $commissions->sum('amount');
        $firstCommission = $commissions->first();
        $currency = $firstCommission ? ($firstCommission->currency ?? 'USD') : 'USD';

        $thisMonthStart = now()->startOfMonth();
        $newReferralsThisMonth = ReferralAccountModel::where('tenant_id', $tenantId)
            ->where('parent_id', $account->id)
            ->where('created_at', '>=', $thisMonthStart)
            ->count();

        return Inertia::render('Referral/Dashboard', [
            'account' => [
                'code' => $account->code,
                'link' => $referralLink,
            ],
            'stats' => [
                'total_direct_children' => $directChildren->count(),
                'total_commissions' => $totalCommissions,
                'currency' => $currency,
                'new_referrals_this_month' => $newReferralsThisMonth,
            ],
            'children' => $directChildrenData,
            'commissions' => $commissions->map(function (ReferralCommissionModel $c) {
                return [
                    'id' => (string) $c->id,
                    'amount' => (float) $c->amount,
                    'currency' => $c->currency,
                    'level' => (int) $c->level,
                    'source_type' => $c->source_type,
                    'source_id' => $c->source_id,
                    'status' => $c->status,
                    'created_at' => optional($c->created_at)->format('Y-m-d H:i'),
                ];
            })->toArray(),
        ]);
    }
}

