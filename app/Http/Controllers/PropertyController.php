<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q')->toString(),
            'type' => $request->string('type')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $query = Property::query()
            ->with(['owner:id,name,email,role', 'investor:id,name,email,role']);

        if (! $request->user()?->isAdmin()) {
            $query->where('owner_id', $request->user()->id);
        }

        $properties = $query
            ->when($filters['q'], function ($q, string $term) {
                $q->where(function ($sub) use ($term) {
                    $sub
                        ->where('name', 'like', '%' . $term . '%')
                        ->orWhere('address', 'like', '%' . $term . '%')
                        ->orWhereHas('investor', fn($inv) => $inv->where('name', 'like', '%' . $term . '%'));
                });
            })
            ->when($filters['type'], fn($q, string $type) => $q->where('type', $type))
            ->when($filters['status'], fn($q, string $status) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn(Property $property) => [
                'id' => $property->id,
                'type' => $property->type,
                'name' => $property->name,
                'featured_image' => $property->featured_image,
                'address' => $property->address,
                'investor' => $property->investor ? [
                    'id' => $property->investor->id,
                    'name' => $property->investor->name,
                    'email' => $property->investor->email,
                    'role' => $property->investor->role,
                ] : null,
                'status' => $property->status,
                'owner' => $property->owner ? [
                    'id' => $property->owner->id,
                    'name' => $property->owner->name,
                    'email' => $property->owner->email,
                    'role' => $property->owner->role,
                ] : null,
                'created_at' => optional($property->created_at)?->toISOString(),
            ]);

        return Inertia::render('properties/Index', [
            'filters' => $filters,
            'properties' => $properties,
            'types' => ['kost', 'villa'],
            'statuses' => ['draft', 'published', 'archived'],
            'canManage' => $request->user()?->isAdmin() || $request->user()?->isHost(),
            'canManageAll' => $request->user()?->isAdmin(),
        ]);
    }

    public function create(Request $request): Response
    {
        $owners = $request->user()?->isAdmin()
            ? \App\Models\User::query()->select(['id', 'name', 'email', 'role'])->orderBy('name')->get()
            : collect();

        $investors = \App\Models\User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->whereIn('role', ['investor', 'host'])
            ->orderBy('name')
            ->get();

        return Inertia::render('properties/Create', [
            'types' => ['kost', 'villa'],
            'statuses' => ['draft', 'published', 'archived'],
            'canManageAll' => $request->user()?->isAdmin(),
            'owners' => $owners,
            'investors' => $investors,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'investor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn($q) => $q->whereIn('role', ['investor', 'host']),
                ),
            ],
            'type' => ['required', 'string', Rule::in(['kost', 'villa'])],
            'name' => ['required', 'string', 'max:255'],
            'featured_image_file' => ['nullable', 'file', 'image', 'max:51200'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'archived'])],
            'gallery_existing' => ['nullable', 'array'],
            'gallery_existing.*' => ['nullable', 'string', 'max:2048'],
            'gallery_files' => ['nullable', 'array'],
            'gallery_files.*' => ['nullable', 'file', 'image', 'max:51200'],
        ]);

        $ownerId = $request->user()?->isAdmin()
            ? ($validated['owner_id'] ?? $request->user()->id)
            : $request->user()->id;

        $existingGallery = array_values(array_filter(
            $validated['gallery_existing'] ?? [],
            fn($item) => is_string($item) && trim($item) !== '',
        ));

        $storedGallery = [];
        foreach ($request->file('gallery_files', []) as $file) {
            $storedGallery[] = $file->store('properties/gallery', 'public');
        }

        $gallery = array_values(array_merge($existingGallery, $storedGallery));

        $featuredImage = null;
        if ($request->file('featured_image_file')) {
            $featuredImage = $request->file('featured_image_file')->store('properties/featured', 'public');
        }

        $property = Property::create([
            'owner_id' => $ownerId,
            'investor_id' => $validated['investor_id'] ?? null,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'featured_image' => $featuredImage,
            'address' => $validated['address'] ?? null,
            'description' => $this->sanitizeRichText($validated['description'] ?? null),
            'status' => $validated['status'],
            'gallery' => $gallery,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property created.')]);

        return to_route('properties.edit', $property);
    }

    public function edit(Request $request, Property $property): Response
    {
        $this->authorizeAccess($request, $property);

        $owners = $request->user()?->isAdmin()
            ? \App\Models\User::query()->select(['id', 'name', 'email', 'role'])->orderBy('name')->get()
            : collect();

        $investors = \App\Models\User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->whereIn('role', ['investor', 'host'])
            ->orderBy('name')
            ->get();

        return Inertia::render('properties/Edit', [
            'property' => [
                'id' => $property->id,
                'owner_id' => $property->owner_id,
                'investor_id' => $property->investor_id,
                'type' => $property->type,
                'name' => $property->name,
                'featured_image' => $property->featured_image,
                'address' => $property->address,
                'description' => $property->description,
                'status' => $property->status,
                'gallery' => $property->gallery ?? [],
                'created_at' => optional($property->created_at)?->toISOString(),
                'updated_at' => optional($property->updated_at)?->toISOString(),
            ],
            'types' => ['kost', 'villa'],
            'statuses' => ['draft', 'published', 'archived'],
            'canManageAll' => $request->user()?->isAdmin(),
            'owners' => $owners,
            'investors' => $investors,
        ]);
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        $this->authorizeAccess($request, $property);

        $validated = $request->validate([
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'investor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn($q) => $q->whereIn('role', ['investor', 'host']),
                ),
            ],
            'type' => ['required', 'string', Rule::in(['kost', 'villa'])],
            'name' => ['required', 'string', 'max:255'],
            'featured_image_remove' => ['nullable', 'boolean'],
            'featured_image_file' => ['nullable', 'file', 'image', 'max:51200'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'archived'])],
            'gallery_existing' => ['nullable', 'array'],
            'gallery_existing.*' => ['nullable', 'string', 'max:2048'],
            'gallery_files' => ['nullable', 'array'],
            'gallery_files.*' => ['nullable', 'file', 'image', 'max:51200'],
        ]);

        if ($request->user()?->isAdmin() && isset($validated['owner_id'])) {
            $property->owner_id = $validated['owner_id'];
        }

        $existingGallery = array_values(array_filter(
            $validated['gallery_existing'] ?? ($property->gallery ?? []),
            fn($item) => is_string($item) && trim($item) !== '',
        ));

        $storedGallery = [];
        foreach ($request->file('gallery_files', []) as $file) {
            $storedGallery[] = $file->store('properties/gallery', 'public');
        }

        $gallery = array_values(array_merge($existingGallery, $storedGallery));

        $featuredImage = $property->featured_image;

        $removeFeaturedImage = filter_var($validated['featured_image_remove'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($removeFeaturedImage) {
            $this->deletePublicFileIfLocal($featuredImage);
            $featuredImage = null;
        }

        if ($request->file('featured_image_file')) {
            $this->deletePublicFileIfLocal($featuredImage);
            $featuredImage = $request->file('featured_image_file')->store('properties/featured', 'public');
        }

        $property->fill([
            'investor_id' => $validated['investor_id'] ?? null,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'featured_image' => $featuredImage,
            'address' => $validated['address'] ?? null,
            'description' => $this->sanitizeRichText($validated['description'] ?? null),
            'status' => $validated['status'],
            'gallery' => $gallery,
        ]);

        $property->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property updated.')]);

        return to_route('properties.edit', $property);
    }

    public function destroy(Request $request, Property $property): RedirectResponse
    {
        $this->authorizeAccess($request, $property);

        $property->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property deleted.')]);

        return to_route('properties.index');
    }

    private function authorizeAccess(Request $request, Property $property): void
    {
        if ($request->user()?->isAdmin()) {
            return;
        }

        abort_unless($request->user() && $property->owner_id === $request->user()->id, 403);
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
