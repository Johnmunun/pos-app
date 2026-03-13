<?php

namespace Src\Infrastructure\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Support\Models\KnowledgeBaseArticleModel;
use Src\Infrastructure\Support\Models\KnowledgeBaseCategoryModel;

class SupportFaqController extends Controller
{
    public function index(Request $request): Response
    {
        $query = KnowledgeBaseArticleModel::query()
            ->where('is_published', true)
            ->with('category')
            ->orderBy('title');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('body', 'like', '%' . $search . '%');
            });
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', (int) $categoryId);
        }

        $articles = $query->paginate(20);

        $articles->getCollection()->transform(function (KnowledgeBaseArticleModel $article) {
            /** @var KnowledgeBaseCategoryModel|null $category */
            $category = $article->category;

            return [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => \Illuminate\Support\Str::limit(strip_tags($article->body), 180),
                'category' => $category ? [
                    'id' => $category->id,
                    'name' => $category->name,
                ] : null,
            ];
        });

        $categories = KnowledgeBaseCategoryModel::orderBy('name')
            ->get(['id', 'name'])
            ->map(function (KnowledgeBaseCategoryModel $c) {
                return ['id' => $c->id, 'name' => $c->name];
            });

        return Inertia::render('Support/Faq', [
            'articles' => $articles,
            'categories' => $categories,
            'filters' => [
                'q' => $request->query('q'),
                'category_id' => $request->query('category_id'),
            ],
        ]);
    }
}

