<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $articles = Article::query()
            ->when($filters['q'], function ($query, string $q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery
                        ->where('title', 'like', '%'.$q.'%')
                        ->orWhere('slug', 'like', '%'.$q.'%');
                });
            })
            ->when($filters['status'], fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'featured_image' => $article->featured_image,
                'status' => $article->status,
                'published_at' => optional($article->published_at)?->toISOString(),
                'created_at' => optional($article->created_at)?->toISOString(),
                'updated_at' => optional($article->updated_at)?->toISOString(),
            ]);

        return Inertia::render('admin/articles/Index', [
            'filters' => $filters,
            'articles' => $articles,
            'statuses' => ['draft', 'published', 'archived'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/articles/Create', [
            'statuses' => ['draft', 'published', 'archived'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'featured_image_file' => ['nullable', 'file', 'image', 'max:51200'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
        ]);

        $slugInput = trim((string) ($validated['slug'] ?? ''));
        $baseSlug = $slugInput !== '' ? Str::slug($slugInput) : Str::slug($validated['title']);
        $slug = $this->generateUniqueSlug($baseSlug);

        $publishedAt = $validated['published_at'] ?? null;
        if ($validated['status'] === 'published' && ! $publishedAt) {
            $publishedAt = now();
        }

        $featuredImage = null;
        if ($request->file('featured_image_file')) {
            $featuredImage = $request->file('featured_image_file')->store('articles/featured', 'public');
        }

        $article = Article::create([
            'author_id' => $request->user()?->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'featured_image' => $featuredImage,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $this->sanitizeRichText($validated['content'] ?? null),
            'status' => $validated['status'],
            'published_at' => $publishedAt,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article created.')]);

        return to_route('admin.articles.edit', $article);
    }

    public function edit(Article $article): Response
    {
        return Inertia::render('admin/articles/Edit', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'featured_image' => $article->featured_image,
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'status' => $article->status,
                'published_at' => optional($article->published_at)?->toISOString(),
                'created_at' => optional($article->created_at)?->toISOString(),
                'updated_at' => optional($article->updated_at)?->toISOString(),
            ],
            'statuses' => ['draft', 'published', 'archived'],
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'featured_image_remove' => ['nullable', 'boolean'],
            'featured_image_file' => ['nullable', 'file', 'image', 'max:51200'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
        ]);

        $slugInput = trim((string) ($validated['slug'] ?? ''));
        $baseSlug = $slugInput !== '' ? Str::slug($slugInput) : Str::slug($validated['title']);
        $slug = $this->generateUniqueSlug($baseSlug, $article->id);

        $publishedAt = $validated['published_at'] ?? null;
        if ($validated['status'] === 'published' && ! $publishedAt) {
            $publishedAt = $article->published_at ?? now();
        }

        $featuredImage = $article->featured_image;
        $removeFeaturedImage = filter_var($validated['featured_image_remove'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($removeFeaturedImage) {
            $this->deletePublicFileIfLocal($featuredImage);
            $featuredImage = null;
        }

        if ($request->file('featured_image_file')) {
            $this->deletePublicFileIfLocal($featuredImage);
            $featuredImage = $request->file('featured_image_file')->store('articles/featured', 'public');
        }

        $article->fill([
            'title' => $validated['title'],
            'slug' => $slug,
            'featured_image' => $featuredImage,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $this->sanitizeRichText($validated['content'] ?? null),
            'status' => $validated['status'],
            'published_at' => $publishedAt,
        ]);
        $article->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article updated.')]);

        return to_route('admin.articles.edit', $article);
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->deletePublicFileIfLocal($article->featured_image);
        $article->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Article deleted.')]);

        return to_route('admin.articles.index');
    }

    private function generateUniqueSlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = trim($baseSlug) !== '' ? $baseSlug : Str::random(8);

        $candidate = $slug;
        $suffix = 2;

        while (Article::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
            if ($suffix > 200) {
                $candidate = $slug.'-'.Str::random(6);
                break;
            }
        }

        return $candidate;
    }

    private function sanitizeRichText(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><a><blockquote><code><pre><h3><h4>';
        $html = strip_tags($html, $allowedTags);
        $html = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $html) ?? '';
        $html = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $html) ?? '';
        $html = preg_replace('/href\s*=\s*("|\')\s*(javascript:|data:)[^"\']*\1/i', '', $html) ?? '';

        $html = trim($html);

        return $html === '' ? null : $html;
    }

    private function deletePublicFileIfLocal(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
