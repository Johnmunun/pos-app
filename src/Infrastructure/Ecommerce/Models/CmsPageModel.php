<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsPageModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ecommerce_cms_pages';

    protected $fillable = [
        'shop_id',
        'title',
        'slug',
        'content',
        'image_path',
        'is_active',
        'published_at',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];
}
