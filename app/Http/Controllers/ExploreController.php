<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q')->toString(),
            'investor_id' => $request->string('investor_id')->toString(),
            'with_photo' => $request->boolean('with_photo'),
            'sort' => $request->string('sort')->toString() ?: 'latest',
        ];

        $investorIds = Property::query()
            ->where('type', 'villa')
            ->where('status', 'published')
            ->whereNotNull('investor_id')
            ->distinct()
            ->pluck('investor_id');

        $investors = User::query()
            ->select(['id', 'name'])
            ->whereIn('id', $investorIds)
            ->orderBy('name')
            ->get();

        $propertiesQuery = Property::query()
            ->where('type', 'villa')
            ->where('status', 'published')
            ->when($filters['q'], function ($q, string $term) {
                $q->where(function ($sub) use ($term) {
                    $sub
                        ->where('name', 'like', '%' . $term . '%')
                        ->orWhere('address', 'like', '%' . $term . '%');
                });
            })
            ->when($filters['with_photo'], fn($q) => $q->whereNotNull('featured_image'))
            ->when(
                is_numeric($filters['investor_id'] ?? null),
                fn($q) => $q->where('investor_id', (int) $filters['investor_id']),
            );

        switch ($filters['sort']) {
            case 'name_asc':
                $propertiesQuery->orderBy('name');
                break;
            case 'name_desc':
                $propertiesQuery->orderByDesc('name');
                break;
            default:
                $propertiesQuery->orderByDesc('id');
                break;
        }

        $properties = $propertiesQuery
            ->paginate(12)
            ->withQueryString()
            ->through(fn(Property $property) => [
                'id' => $property->id,
                'type' => $property->type,
                'name' => $property->name,
                'featured_image' => $property->featured_image,
                'address' => $property->address,
            ]);

        return Inertia::render('guest/explore/Index', [
            'filters' => $filters,
            'properties' => $properties,
            'investors' => $investors,
        ]);
    }

    public function show(Request $request, Property $property): Response
    {
        $canPreviewUnpublished = $request->user()?->isAdmin()
            || ($request->user()?->isHost() && $property->owner_id === $request->user()->id);

        abort_unless($property->status === 'published' || $canPreviewUnpublished, 404);
        abort_unless($property->type === 'villa', 404);

        $blocked = Booking::query()
            ->where('property_id', $property->id)
            ->whereIn('status', ['pending_payment', 'confirmed'])
            ->orderBy('check_in_date')
            ->get()
            ->map(fn(Booking $b) => [
                'check_in_date' => optional($b->check_in_date)->toDateString(),
                'check_out_date' => optional($b->check_out_date)->toDateString(),
                'status' => $b->status,
            ])
            ->all();

        return Inertia::render('guest/explore/Show', [
            'property' => [
                'id' => $property->id,
                'type' => $property->type,
                'name' => $property->name,
                'featured_image' => $property->featured_image,
                'address' => $property->address,
                'description' => $property->description,
                'gallery' => $property->gallery ?? [],
                'status' => $property->status,
            ],
            'blocked' => $blocked,
        ]);
    }
}
