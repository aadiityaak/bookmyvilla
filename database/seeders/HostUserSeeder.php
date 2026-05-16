<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class HostUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'host@example.com'],
            [
                'name' => 'Host',
                'role' => 'host',
                'password' => 'password',
                'email_verified_at' => now(),
                'disabled_at' => null,
            ],
        );

        User::factory()->count(8)->create([
            'role' => 'host',
        ]);
    }
}
