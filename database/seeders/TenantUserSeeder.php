<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TenantUserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Tenant',
            'email' => 'tenant@example.com',
            'role' => 'tenant',
        ]);
    }
}

