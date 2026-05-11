<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with('owner:id,name,email,role');

        if (! $request->user()?->isAdmin()) {
            $query->where('owner_id', $request->user()->id);
        }

        $properties = $query
            ->when($filters['q'], function ($q, string $term) {
                $q->where(function ($sub) use ($term) {
                    $sub
                        ->where('name', 'like', '%'.$term.'%')
                        ->orWhere('address', 'like', '%'.$term.'%')
                        ->orWhere('investor_name', 'like', '%'.$term.'%');
                });
            })
            ->when($filters['type'], fn ($q, string $type) => $q->where('type', $type))
            ->when($filters['status'], fn ($q, string $status) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Property $property) => [
                'id' => $property->id,
                'type' => $property->type,
                'name' => $property->name,
                'address' => $property->address,
                'investor_name' => $property->investor_name,
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
        return Inertia::render('properties/Create', [
            'types' => ['kost', 'villa'],
            'statuses' => ['draft', 'published', 'archived'],
            'canManageAll' => $request->user()?->isAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'type' => ['required', 'string', Rule::in(['kost', 'villa'])],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'investor_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'archived'])],
        ]);

        $ownerId = $request->user()?->isAdmin()
            ? ($validated['owner_id'] ?? $request->user()->id)
            : $request->user()->id;

        $property = Property::create([
            ...$validated,
            'owner_id' => $ownerId,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property created.')]);

        return to_route('properties.edit', $property);
    }

    public function edit(Request $request, Property $property): Response
    {
        $this->authorizeAccess($request, $property);

        return Inertia::render('properties/Edit', [
            'property' => [
                'id' => $property->id,
                'owner_id' => $property->owner_id,
                'type' => $property->type,
                'name' => $property->name,
                'address' => $property->address,
                'description' => $property->description,
                'investor_name' => $property->investor_name,
                'status' => $property->status,
                'created_at' => optional($property->created_at)?->toISOString(),
                'updated_at' => optional($property->updated_at)?->toISOString(),
            ],
            'types' => ['kost', 'villa'],
            'statuses' => ['draft', 'published', 'archived'],
            'canManageAll' => $request->user()?->isAdmin(),
        ]);
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        $this->authorizeAccess($request, $property);

        $validated = $request->validate([
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'type' => ['required', 'string', Rule::in(['kost', 'villa'])],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'investor_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'archived'])],
        ]);

        if ($request->user()?->isAdmin() && isset($validated['owner_id'])) {
            $property->owner_id = $validated['owner_id'];
        }

        $property->fill([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'description' => $validated['description'] ?? null,
            'investor_name' => $validated['investor_name'] ?? null,
            'status' => $validated['status'],
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
}

