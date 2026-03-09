<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CmsMediaModel extends Model
{
    use HasUuids;

    protected $table = 'ecommerce_cms_media';

    protected $fillable = [
        'shop_id',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];
}
