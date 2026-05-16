<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Property;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q')->toString(),
        ];

        $properties = Property::query()
            ->where('type', 'villa')
            ->where('status', 'published')
            ->when($filters['q'], function ($q, string $term) {
                $q->where(function ($sub) use ($term) {
                    $sub
                        ->where('name', 'like', '%' . $term . '%')
                        ->orWhere('address', 'like', '%' . $term . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn(Property $property) => [
                'id' => $property->id,
                'type' => $property->type,
                'name' => $property->name,
                'featured_image' => $property->featured_image,
                'address' => $property->address,
            ]);

        return Inertia::render('explore/Index', [
            'filters' => $filters,
            'properties' => $properties,
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

        return Inertia::render('explore/Show', [
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

