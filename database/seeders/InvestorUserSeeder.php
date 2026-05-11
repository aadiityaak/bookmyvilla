<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InvestorUserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Investor',
            'email' => 'investor@example.com',
            'role' => 'investor',
            'password' => bcrypt('password'),
        ]);
    }
}
