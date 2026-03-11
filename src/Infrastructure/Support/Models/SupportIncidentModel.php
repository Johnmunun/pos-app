<?php

namespace Src\Infrastructure\Support\Models;

use Illuminate\Database\Eloquent\Model;

class SupportIncidentModel extends Model
{
    protected $table = 'support_incidents';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'severity',
        'status',
        'started_at',
        'resolved_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}

