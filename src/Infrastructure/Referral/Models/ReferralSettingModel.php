<?php

namespace Src\Infrastructure\Referral\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralSettingModel extends Model
{
    protected $table = 'referral_settings';
    protected $primaryKey = 'tenant_id';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'enabled',
        'commission_type',
        'commission_value',
        'max_levels',
        'enabled_modules',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'commission_value' => 'float',
        'max_levels' => 'integer',
        'enabled_modules' => 'array',
    ];
}

