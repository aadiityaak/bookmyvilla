<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
  public function run(): void
  {
    $faker = fake();
    $faker->unique(true);

    $propertyIds = Property::query()
      ->where('type', 'villa')
      ->where('status', 'published')
      ->pluck('id')
      ->all();

    $guestIds = User::query()
      ->whereIn('role', ['tenant'])
      ->pluck('id')
      ->all();

    if (count($propertyIds) === 0) {
      $propertyIds = Property::query()->pluck('id')->all();
    }

    if (count($guestIds) === 0) {
      $guestIds = User::query()->pluck('id')->all();
    }

    if (count($propertyIds) === 0 || count($guestIds) === 0) {
      return;
    }

    $statuses = ['pending_payment', 'confirmed', 'cancelled'];

    for ($i = 0; $i < 60; $i++) {
      $propertyId = $propertyIds[array_rand($propertyIds)];
      $guestId = $guestIds[array_rand($guestIds)];

      $status = $i < 15 ? 'confirmed' : $statuses[array_rand($statuses)];

      $checkIn = $faker->dateTimeBetween('-30 days', '+30 days');
      $nights = $faker->numberBetween(1, 7);
      $checkOut = (clone $checkIn);
      $checkOut->modify("+{$nights} days");

      $guestsCount = $faker->numberBetween(1, 8);

      $base = $faker->numberBetween(450_000, 3_500_000);
      $totalAmount = $status === 'cancelled' ? null : ($base * $nights);

      Booking::create([
        'property_id' => $propertyId,
        'guest_user_id' => $guestId,
        'check_in_date' => $checkIn->format('Y-m-d'),
        'check_out_date' => $checkOut->format('Y-m-d'),
        'guests_count' => $guestsCount,
        'status' => $status,
        'total_amount' => $totalAmount,
        'currency' => 'IDR',
        'price_snapshot' => [
          'nights' => $nights,
          'base_per_night' => $base,
          'subtotal' => $totalAmount,
        ],
      ]);
    }
  }
}
