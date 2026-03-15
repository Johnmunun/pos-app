<?php

namespace Src\Infrastructure\ModuleOnboarding\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleOnboardingStatusModel extends Model
{
    protected $table = 'module_onboarding_status';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'module_name',
        'steps_completed',
        'status',
        'updated_at',
    ];

    protected $casts = [
        'steps_completed' => 'array',
        'status' => 'integer',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
