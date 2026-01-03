<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AppSetting;
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
        AppSetting::firstOrCreate([], [
            'app_icon_color' => '#f59e0b',
        ]);

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'role' => 'admin',
                'password' => Hash::make('1234'),
                'pin' => Hash::make('1234'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'vendedor@example.com'],
            [
                'name' => 'Vendedor',
                'username' => 'vendedor',
                'role' => 'seller',
                'password' => Hash::make('1234'),
                'pin' => Hash::make('1234'),
            ],
        );
    }
}
