<?php

namespace Src\Infrastructure\Common\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AiImageGenerationRequest extends Model
{
    use HasUuids;

    protected $table = 'ai_image_generation_requests';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'feature_code',
        'context',
        'status',
        'count',
        'title',
        'description',
        'result_images',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'result_images' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
