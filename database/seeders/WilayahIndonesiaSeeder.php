<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WilayahIndonesiaSeeder extends Seeder
{
    public function run(): void
    {
        $paths = [
            'C:\\Users\\ASUS\\Downloads\\wilayah_indonesia.sql',
            base_path('database/seeders/wilayah_indonesia.sql'),
        ];

        $path = null;
        foreach ($paths as $candidate) {
            if (is_string($candidate) && file_exists($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if (! $path) {
            return;
        }

        DB::unprepared(file_get_contents($path));
    }
}
