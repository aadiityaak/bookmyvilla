<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Booking;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

        $booking = Booking::create([
            'property_id' => $property->id,
            'guest_user_id' => $request->user()->id,
            'check_in_date' => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'guests_count' => $validated['guests_count'],
            'status' => 'pending_payment',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Booking created.')]);

        return to_route('bookings.payment', $booking);
    }

    public function payment(Request $request, Booking $booking): Response
    {
        abort_unless($booking->guest_user_id === $request->user()->id, 403);

        $booking->loadMissing(['property:id,name,featured_image,address']);

        $paymentValue = [];
        if (Schema::hasTable('app_settings')) {
            $row = AppSetting::query()->where('key', 'payment')->first();
            $paymentValue = is_array($row?->value) ? $row->value : [];
        }

        $banks = is_array($paymentValue['banks'] ?? null) ? $paymentValue['banks'] : [];
        $normalizedBanks = [];
        foreach ($banks as $b) {
            if (! is_array($b)) {
                continue;
            }

            $normalizedBanks[] = [
                'bank_name' => is_string($b['bank_name'] ?? null) ? $b['bank_name'] : null,
                'account_name' => is_string($b['account_name'] ?? null) ? $b['account_name'] : null,
                'account_number' => is_string($b['account_number'] ?? null) ? $b['account_number'] : null,
            ];
        }

        if (count($normalizedBanks) === 0) {
            $legacy = [
                'bank_name' => $paymentValue['bank_name'] ?? null,
                'account_name' => $paymentValue['bank_account_name'] ?? null,
                'account_number' => $paymentValue['bank_account_number'] ?? null,
            ];

            $hasAny = false;
            foreach ($legacy as $value) {
                if (is_string($value) && trim($value) !== '') {
                    $hasAny = true;
                    break;
                }
            }

            if ($hasAny) {
                $normalizedBanks = [[
                    'bank_name' => is_string($legacy['bank_name']) ? $legacy['bank_name'] : null,
                    'account_name' => is_string($legacy['account_name']) ? $legacy['account_name'] : null,
                    'account_number' => is_string($legacy['account_number']) ? $legacy['account_number'] : null,
                ]];
            }
        }

        $qrisPath = is_string($paymentValue['qris_image_path'] ?? null) ? $paymentValue['qris_image_path'] : null;
        $qrisUrl = $qrisPath && $qrisPath !== '' ? '/storage/' . ltrim($qrisPath, '/') : null;

        $proofPath = is_string($booking->payment_proof_path ?? null) ? $booking->payment_proof_path : null;
        $proofUrl = $proofPath && $proofPath !== '' ? '/storage/' . ltrim($proofPath, '/') : null;

        return Inertia::render('guest/bookings/Payment', [
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'check_in_date' => optional($booking->check_in_date)->toDateString(),
                'check_out_date' => optional($booking->check_out_date)->toDateString(),
                'guests_count' => $booking->guests_count,
                'payment_proof_url' => $proofUrl,
                'payment_proof_uploaded_at' => optional($booking->payment_proof_uploaded_at)?->toISOString(),
                'created_at' => optional($booking->created_at)?->toISOString(),
                'property' => $booking->property ? [
                    'id' => $booking->property->id,
                    'name' => $booking->property->name,
                    'featured_image' => $booking->property->featured_image,
                    'address' => $booking->property->address,
                ] : null,
            ],
            'payment' => [
                'banks' => $normalizedBanks,
                'qris_image_url' => $qrisUrl,
            ],
        ]);
    }

    public function paymentConfirm(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->guest_user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'payment_proof_file' => ['required', 'file', 'image', 'max:5120'],
        ]);

        abort_unless(Schema::hasTable('bookings'), 500);

        $oldPath = is_string($booking->payment_proof_path ?? null) ? $booking->payment_proof_path : null;

        $newPath = $request->file('payment_proof_file')->store('payment_proofs', 'public');

        if (is_string($oldPath) && $oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $booking->payment_proof_path = $newPath;
        $booking->payment_proof_uploaded_at = now();
        $booking->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bukti pembayaran terkirim.']);

        return to_route('bookings.payment', $booking);
    }
}
