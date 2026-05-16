<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $bookings = Booking::query()
            ->with(['property:id,name,featured_image'])
            ->where('guest_user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(15)
            ->through(fn(Booking $booking) => [
                'id' => $booking->id,
                'property' => $booking->property ? [
                    'id' => $booking->property->id,
                    'name' => $booking->property->name,
                    'featured_image' => $booking->property->featured_image,
                ] : null,
                'check_in_date' => optional($booking->check_in_date)->toDateString(),
                'check_out_date' => optional($booking->check_out_date)->toDateString(),
                'guests_count' => $booking->guests_count,
                'status' => $booking->status,
                'created_at' => optional($booking->created_at)?->toISOString(),
            ]);

        return Inertia::render('guest/bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('properties', 'id')],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'guests_count' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $property = Property::query()->findOrFail($validated['property_id']);

        abort_unless($property->type === 'villa', 404);
        abort_unless($property->status === 'published', 404);

        $overlapExists = Booking::query()
            ->where('property_id', $property->id)
            ->whereIn('status', ['pending_payment', 'confirmed'])
            ->where(function ($q) use ($validated) {
                $q
                    ->where('check_in_date', '<', $validated['check_out_date'])
                    ->where('check_out_date', '>', $validated['check_in_date']);
            })
            ->exists();

        if ($overlapExists) {
            return back()
                ->withErrors(['check_in_date' => __('Tanggal tidak tersedia untuk periode tersebut.')])
                ->withInput();
        }

        Booking::create([
            'property_id' => $property->id,
            'guest_user_id' => $request->user()->id,
            'check_in_date' => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'guests_count' => $validated['guests_count'],
            'status' => 'pending_payment',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Booking created.')]);

        return to_route('bookings.index');
    }
}
