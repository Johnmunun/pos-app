<?php

namespace Src\Infrastructure\Logs\Models;

use Illuminate\Database\Eloquent\Model;

class UserLoginHistoryModel extends Model
{
    protected $table = 'user_login_histories';

    protected $fillable = [
        'user_id',
        'logged_in_at',
        'ip_address',
        'user_agent',
        'device',
        'status',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
    ];
}

