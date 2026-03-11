<?php

namespace Src\Infrastructure\Referral\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReferralCommissionModel extends Model
{
    use HasUuids;

    protected $table = 'referral_commissions';

    protected $fillable = [
        'tenant_id',
        'referrer_account_id',
        'referred_user_id',
        'source_type',
        'source_id',
        'level',
        'amount',
        'currency',
        'status',
    ];

    protected $casts = [
        'amount' => 'float',
        'level' => 'integer',
    ];
}

