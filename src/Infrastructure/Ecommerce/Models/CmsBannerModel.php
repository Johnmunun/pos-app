<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsBannerModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ecommerce_cms_banners';

    protected $fillable = [
        'shop_id',
        'title',
        'image_path',
        'link',
        'position',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const POSITION_HOMEPAGE = 'homepage';
    public const POSITION_PROMOTION = 'promotion';
    public const POSITION_SLIDER = 'slider';
}
