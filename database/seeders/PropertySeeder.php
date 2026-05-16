<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
  public function run(): void
  {
    $ownerIds = User::query()
      ->whereIn('role', ['admin', 'host'])
      ->pluck('id')
      ->all();

    $investorIds = User::query()
      ->whereIn('role', ['investor', 'host'])
      ->pluck('id')
      ->all();

    if (count($ownerIds) === 0) {
      return;
    }

    $types = ['kost', 'villa'];
    $statuses = ['draft', 'published', 'archived'];

    for ($i = 0; $i < 80; $i++) {
      $type = $types[array_rand($types)];
      $status = $statuses[array_rand($statuses)];

      $namePrefix = $type === 'villa' ? 'Villa' : 'Kost';
      $name = $namePrefix . ' ' . fake()->unique()->streetName();

      $galleryCount = fake()->numberBetween(0, 4);
      $gallery = [];
      for ($g = 0; $g < $galleryCount; $g++) {
        $gallery[] = 'https://picsum.photos/seed/' . fake()->uuid() . '/1200/800';
      }

      Property::create([
        'owner_id' => $ownerIds[array_rand($ownerIds)],
        'investor_id' => count($investorIds)
          ? fake()->optional(0.7)->randomElement($investorIds)
          : null,
        'type' => $type,
        'name' => $name,
        'featured_image' => fake()->optional(0.8)->passthrough('https://picsum.photos/seed/' . fake()->uuid() . '/1200/800'),
        'address' => fake()->optional(0.9)->address(),
        'description' => fake()->optional(0.7)->paragraphs(asText: true),
        'status' => $status,
        'gallery' => $galleryCount ? $gallery : null,
      ]);
    }
  }
}
