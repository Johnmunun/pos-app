<?php

namespace Src\Infrastructure\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBaseCategoryModel extends Model
{
    protected $table = 'knowledge_base_categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleModel::class, 'category_id');
    }
}

