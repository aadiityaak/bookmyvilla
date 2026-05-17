<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PropertySeeder extends Seeder
{
  public function run(): void
  {
    $faker = fake();
    $faker->unique(true);

    $ownerIds = User::query()
      ->whereIn('role', ['host'])
      ->pluck('id')
      ->all();

    if (count($ownerIds) === 0) {
      $ownerIds = User::query()
        ->whereIn('role', ['admin'])
        ->pluck('id')
        ->all();
    }

    $investorIds = User::query()
      ->whereIn('role', ['investor', 'host'])
      ->pluck('id')
      ->all();

    if (count($ownerIds) === 0) {
      return;
    }

    $provinceIds = Schema::hasTable('reg_provinces')
      ? DB::table('reg_provinces')->pluck('id')->all()
      : [];

    $hasProvinceId = Schema::hasColumn('properties', 'province_id');
    $hasRegencyId = Schema::hasColumn('properties', 'regency_id');
    $hasLatitude = Schema::hasColumn('properties', 'latitude');
    $hasLongitude = Schema::hasColumn('properties', 'longitude');
    $hasAmenities = Schema::hasColumn('properties', 'amenities');

    $types = ['kost', 'villa'];
    $statuses = ['draft', 'published', 'archived'];

    $amenitiesCatalog = [
      'common' => [
        'Wifi',
        'Parkir motor',
        'Parkir mobil',
        'Air panas',
        'TV',
        'Dapur',
        'Dispenser',
        'Area merokok',
        'Layanan kebersihan',
        'Keamanan 24 jam',
        'CCTV',
        'Akses kartu/kunci',
        'Kamar mandi',
        'Kamar mandi dalam',
        'Kamar mandi luar',
        'Mushola',
        'Ruang tamu',
        'Rooftop',
        'Taman',
        'Balkon/teras',
        'Pemandangan',
      ],
      'kost' => [
        'AC',
        'Kipas angin',
        'Kasur',
        'Lemari',
        'Meja belajar',
        'Kursi',
        'Laundry',
        'Dapur bersama',
        'Kulkas bersama',
        'Kompor bersama',
        'Termasuk listrik',
        'Termasuk air',
        'Pet friendly',
        'Khusus putra',
        'Khusus putri',
        'Pasangan menikah',
      ],
      'villa' => [
        'Kolam renang',
        'Kolam renang pribadi',
        'BBQ grill',
        'Sarapan',
        'Dapur lengkap',
        'Chef on request',
        'Smart TV',
        'Netflix',
        'Sound system',
        'Bathtub',
        'Gym',
        'Playground',
        'Meja biliar',
        'Pingpong',
        'Karaoke',
        'View laut',
        'View gunung',
        'Akses pantai',
        'Taman luas',
        'Area api unggun',
      ],
    ];

    for ($i = 0; $i < 80; $i++) {
      if ($i < 12) {
        $type = 'villa';
        $status = 'published';
      } else if ($i < 24) {
        $type = 'kost';
        $status = 'published';
      } else {
        $type = $types[array_rand($types)];
        $status = $statuses[array_rand($statuses)];
      }

      $namePrefix = $type === 'villa' ? 'Villa' : 'Kost';
      $name = $namePrefix . ' ' . $faker->unique()->streetName();

      $galleryCount = $status === 'published' ? $faker->numberBetween(1, 4) : $faker->numberBetween(0, 4);
      $gallery = [];
      for ($g = 0; $g < $galleryCount; $g++) {
        $gallery[] = 'https://picsum.photos/seed/' . $faker->uuid() . '/1200/800';
      }

      $provinceId = $hasProvinceId && count($provinceIds) ? $faker->randomElement($provinceIds) : null;
      $regencyId = null;
      if ($hasProvinceId && $hasRegencyId && $provinceId && Schema::hasTable('reg_regencies')) {
        $regencyIds = DB::table('reg_regencies')->where('province_id', $provinceId)->pluck('id')->all();
        $regencyId = count($regencyIds) ? $faker->randomElement($regencyIds) : null;
      }

      $latitude = $hasLatitude ? $faker->latitude(-7.95, -7.70) : null;
      $longitude = $hasLongitude ? $faker->longitude(110.20, 110.60) : null;

      $descriptionParagraphs = $faker->optional(0.75)->paragraphs($faker->numberBetween(2, 4));
      $description = is_array($descriptionParagraphs) && count($descriptionParagraphs)
        ? '<p>' . implode('</p><p>', $descriptionParagraphs) . '</p>'
        : null;

      $amenities = null;
      if ($hasAmenities) {
        $chance = $status === 'published' ? 92 : 55;
        if ($faker->boolean($chance)) {
          $pool = array_values(array_unique(array_merge(
            $amenitiesCatalog['common'],
            $amenitiesCatalog[$type] ?? [],
          )));

          $countMin = $type === 'villa' ? 6 : 5;
          $countMax = $type === 'villa' ? 12 : 10;
          $count = min(count($pool), $faker->numberBetween($countMin, $countMax));
          $items = $faker->randomElements($pool, $count);
          $amenities = implode("\n", $items);
        }
      }

      $payload = [
        'owner_id' => $ownerIds[array_rand($ownerIds)],
        'investor_id' => count($investorIds)
          ? $faker->optional(0.7)->randomElement($investorIds)
          : null,
        'type' => $type,
        'name' => $name,
        'featured_image' => $faker->optional($status === 'published' ? 0.95 : 0.8)->passthrough(
          'https://picsum.photos/seed/' . $faker->uuid() . '/1200/800'
        ),
        'address' => $faker->optional(0.9)->address(),
        'description' => $description,
        'amenities' => $amenities,
        'status' => $status,
        'gallery' => $galleryCount ? $gallery : null,
      ];

      if ($hasProvinceId) {
        $payload['province_id'] = $provinceId;
      }
      if ($hasRegencyId) {
        $payload['regency_id'] = $regencyId;
      }
      if ($hasLatitude) {
        $payload['latitude'] = $latitude;
      }
      if ($hasLongitude) {
        $payload['longitude'] = $longitude;
      }

      Property::create($payload);
    }
  }
}
