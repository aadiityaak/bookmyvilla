<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => 'password',
                'email_verified_at' => now(),
                'disabled_at' => null,
            ],
        );

        User::factory()->count(2)->create([
            'role' => 'admin',
        ]);
    }
}
