<?php

namespace Src\Infrastructure\Referral\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReferralAccountModel extends Model
{
    use HasUuids;

    protected $table = 'referral_accounts';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'parent_id',
        'code',
        'total_referrals',
        'total_referred_revenue',
        'total_commissions_amount',
        'currency',
    ];

    protected $casts = [
        'total_referrals' => 'integer',
        'total_referred_revenue' => 'float',
        'total_commissions_amount' => 'float',
    ];
}

