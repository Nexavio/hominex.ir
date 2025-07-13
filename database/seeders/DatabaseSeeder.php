<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users
        User::factory(10)->create();

        // Create specific test user
        User::factory()->create([
            'phone' => '09123456789',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'user_type' => UserRole::REGULAR,
            'phone_verified_at' => now(),
        ]);

        // Create admin user
        User::factory()->admin()->create([
            'phone' => '09111111111',
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone_verified_at' => now(),
        ]);

        // Create consultant user
        User::factory()->consultant()->create([
            'phone' => '09222222222',
            'full_name' => 'Consultant User',
            'email' => 'consultant@example.com',
            'phone_verified_at' => now(),
        ]);
    }
}
