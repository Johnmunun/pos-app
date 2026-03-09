<?php

namespace Src\Infrastructure\Ecommerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CmsBlogCategoryModel extends Model
{
    use HasUuids;

    protected $table = 'ecommerce_cms_blog_categories';

    protected $fillable = [
        'shop_id',
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function articles()
    {
        return $this->hasMany(CmsBlogArticleModel::class, 'category_id');
    }
}
