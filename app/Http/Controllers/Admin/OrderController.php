<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $orders = Booking::query()
            ->with([
                'property:id,name,featured_image',
                'guest:id,name,email',
            ])
            ->when($filters['q'], function ($query, string $q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery
                        ->where('id', $q)
                        ->orWhereHas('property', fn ($p) => $p->where('name', 'like', '%'.$q.'%'))
                        ->orWhereHas('guest', fn ($g) => $g
                            ->where('name', 'like', '%'.$q.'%')
                            ->orWhere('email', 'like', '%'.$q.'%'));
                });
            })
            ->when($filters['status'], fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Booking $booking) => [
                'id' => $booking->id,
                'property' => $booking->property ? [
                    'id' => $booking->property->id,
                    'name' => $booking->property->name,
                    'featured_image' => $booking->property->featured_image,
                ] : null,
                'guest' => $booking->guest ? [
                    'id' => $booking->guest->id,
                    'name' => $booking->guest->name,
                    'email' => $booking->guest->email,
                ] : null,
                'check_in_date' => optional($booking->check_in_date)->toDateString(),
                'check_out_date' => optional($booking->check_out_date)->toDateString(),
                'guests_count' => $booking->guests_count,
                'status' => $booking->status,
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency,
                'created_at' => optional($booking->created_at)?->toISOString(),
            ]);

        return Inertia::render('admin/orders/Index', [
            'filters' => $filters,
            'orders' => $orders,
            'statuses' => ['pending_payment', 'confirmed', 'cancelled'],
        ]);
    }

    public function edit(Booking $booking): Response
    {
        $booking->loadMissing([
            'property:id,name,featured_image,address',
            'guest:id,name,email',
        ]);

        return Inertia::render('admin/orders/Edit', [
            'order' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'check_in_date' => optional($booking->check_in_date)->toDateString(),
                'check_out_date' => optional($booking->check_out_date)->toDateString(),
                'guests_count' => $booking->guests_count,
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency,
                'price_snapshot' => $booking->price_snapshot,
                'created_at' => optional($booking->created_at)?->toISOString(),
                'updated_at' => optional($booking->updated_at)?->toISOString(),
                'property' => $booking->property ? [
                    'id' => $booking->property->id,
                    'name' => $booking->property->name,
                    'featured_image' => $booking->property->featured_image,
                    'address' => $booking->property->address,
                ] : null,
                'guest' => $booking->guest ? [
                    'id' => $booking->guest->id,
                    'name' => $booking->guest->name,
                    'email' => $booking->guest->email,
                ] : null,
            ],
            'statuses' => ['pending_payment', 'confirmed', 'cancelled'],
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['pending_payment', 'confirmed', 'cancelled'])],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $booking->fill([
            'status' => $validated['status'],
            'total_amount' => $validated['total_amount'] ?? null,
            'currency' => $validated['currency'] ?? $booking->currency,
        ]);
        $booking->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Order updated.')]);

        return to_route('admin.orders.edit', $booking);
    }
}
