<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => Hash::make('1234'),
            'pin' => Hash::make('1234'),
        ]);

        User::factory()->create([
            'name' => 'Vendedor',
            'email' => 'vendedor@example.com',
            'role' => 'seller',
            'password' => Hash::make('1234'),
            'pin' => Hash::make('1234'),
        ]);
    }
}
