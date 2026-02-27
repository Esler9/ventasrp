<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Client;
use App\Models\CashRegister;
use App\Models\RestaurantTable;
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
            'primary_color' => '#0773A4',
            'secondary_color' => '#0EA5E9',
            'currency_code' => 'GTQ',
            'currency_symbol' => 'Q',
            'business_mode' => 'minorista',
        ]);

        collect([
            ['code' => 'MESA-01', 'name' => 'Mesa 1', 'is_takeaway' => false, 'sort_order' => 1],
            ['code' => 'MESA-02', 'name' => 'Mesa 2', 'is_takeaway' => false, 'sort_order' => 2],
            ['code' => 'MESA-03', 'name' => 'Mesa 3', 'is_takeaway' => false, 'sort_order' => 3],
            ['code' => 'PARA-LLEVAR', 'name' => 'Para llevar', 'is_takeaway' => true, 'sort_order' => 99],
        ])->each(function (array $table): void {
            RestaurantTable::firstOrCreate(
                ['code' => $table['code']],
                [
                    'name' => $table['name'],
                    'is_takeaway' => $table['is_takeaway'],
                    'is_active' => true,
                    'sort_order' => $table['sort_order'],
                ],
            );
        });

        CashRegister::firstOrCreate(
            ['name' => 'Caja #01'],
            [
                'branch_name' => 'Sucursal Centro',
                'is_active' => true,
            ],
        );

        collect([
            ['name' => 'Bebidas', 'slug' => 'bebidas'],
            ['name' => 'Snacks', 'slug' => 'snacks'],
            ['name' => 'Lácteos', 'slug' => 'lacteos'],
            ['name' => 'Higiene', 'slug' => 'higiene'],
            ['name' => 'Limpieza', 'slug' => 'limpieza'],
        ])->each(function (array $item): void {
            Category::firstOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => null,
                    'is_active' => true,
                ],
            );
        });

        collect([
            ['name' => 'Consumidor Final', 'tax_id' => 'CF'],
            ['name' => 'Cliente Mostrador', 'tax_id' => null],
        ])->each(function (array $item): void {
            Client::firstOrCreate(
                ['name' => $item['name']],
                [
                    'phone' => null,
                    'email' => null,
                    'tax_id' => $item['tax_id'],
                    'address' => null,
                    'is_active' => true,
                ],
            );
        });

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'role' => 'owner_manager',
                'password' => Hash::make('1234'),
                'pin' => Hash::make('1234'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'vendedor@example.com'],
            [
                'name' => 'Vendedor',
                'username' => 'vendedor',
                'role' => 'seller_cashier',
                'password' => Hash::make('1234'),
                'pin' => Hash::make('1234'),
            ],
        );
    }
}
