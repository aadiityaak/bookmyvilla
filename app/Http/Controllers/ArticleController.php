<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function show(Article $article): Response
    {
        if ($article->status !== 'published') {
            abort(404);
        }

        $article->loadMissing('author:id,name');

        $latest = Article::query()
            ->select(['id', 'title', 'slug', 'featured_image', 'published_at', 'created_at'])
            ->where('status', 'published')
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Article $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'featured_image' => $a->featured_image,
                'published_at' => optional($a->published_at ?? $a->created_at)?->toISOString(),
            ]);

        return Inertia::render('guest/articles/Show', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'featured_image' => $article->featured_image,
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'published_at' => optional($article->published_at ?? $article->created_at)?->toISOString(),
                'author' => $article->author ? ['name' => $article->author->name] : null,
            ],
            'latest' => $latest,
        ]);
    }
}
