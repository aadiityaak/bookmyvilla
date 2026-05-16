<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InvestorUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'investor@example.com'],
            [
                'name' => 'Investor',
                'role' => 'investor',
                'password' => 'password',
                'email_verified_at' => now(),
                'disabled_at' => null,
            ],
        );

        User::factory()->count(8)->create([
            'role' => 'investor',
        ]);
    }
}
