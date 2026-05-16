<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TenantUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'tenant@example.com'],
            [
                'name' => 'Tenant',
                'role' => 'tenant',
                'password' => 'password',
                'email_verified_at' => now(),
                'disabled_at' => null,
            ],
        );

        User::factory()->count(20)->create([
            'role' => 'tenant',
        ]);
    }
}
