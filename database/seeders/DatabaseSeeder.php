<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            WilayahIndonesiaSeeder::class,
            AdminUserSeeder::class,
            HostUserSeeder::class,
            InvestorUserSeeder::class,
            TenantUserSeeder::class,
            ArticleSeeder::class,
            PropertySeeder::class,
        ]);
    }
}
