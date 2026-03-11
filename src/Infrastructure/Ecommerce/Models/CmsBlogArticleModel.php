<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $shop_id
 * @property string|null $category_id
 * @property string $title
 * @property string $slug
 * @property string|null $content
 * @property string|null $image_path
 * @property string|null $excerpt
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $disk
 *
 * @property-read CmsBlogCategoryModel|null $category
 */
class CmsBlogArticleModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ecommerce_cms_blog_articles';

    protected $fillable = [
        'shop_id',
        'category_id',
        'title',
        'slug',
        'content',
        'image_path',
        'excerpt',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(CmsBlogCategoryModel::class, 'category_id');
    }
}
