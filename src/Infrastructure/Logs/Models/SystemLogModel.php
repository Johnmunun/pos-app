<?php

namespace Src\Infrastructure\Logs\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLogModel extends Model
{
    protected $table = 'system_logs';

    protected $fillable = [
        'logged_at',
        'level',
        'module',
        'user_id',
        'ip_address',
        'message',
        'context',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];
}

