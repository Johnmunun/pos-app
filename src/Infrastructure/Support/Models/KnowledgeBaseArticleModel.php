<?php

namespace Src\Infrastructure\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseArticleModel extends Model
{
    protected $table = 'knowledge_base_articles';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'title',
        'slug',
        'body',
        'is_published',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategoryModel::class, 'category_id');
    }
}

