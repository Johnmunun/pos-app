<?php

namespace Src\Infrastructure\Logs\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivityModel extends Model
{
    protected $table = 'user_activities';

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'route',
        'ip_address',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}

