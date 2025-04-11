<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        // Create an admin user for testing.
        User::factory()->create([
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'avatar' => null,
            'location' => collect(config('locations.options'))->keys()->first(),
            'password' => Hash::make('password'),
        ]);

        // Create 10 random users using the factory.
        User::factory()->count(10)->create();
    }
}
