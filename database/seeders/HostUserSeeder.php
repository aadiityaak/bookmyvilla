<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class HostUserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Host',
            'email' => 'host@example.com',
            'role' => 'host',
        ]);
    }
}

