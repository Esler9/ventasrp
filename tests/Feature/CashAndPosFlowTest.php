<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashAndPosFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_sale_requires_open_cash_session(): void
    {
        $user = User::factory()->create(['role' => 'seller_cashier']);
        $product = Product::create([
            'name' => 'Producto A',
            'unit_label' => 'Unidad',
            'sku' => 'SKU-A',
            'price' => 10,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/pos/sales', [
                'customer_name' => 'CF',
                'items' => [[
                    'product_id' => $product->id,
                    'presentation_factor' => 1,
                    'quantity' => 1,
                    'price' => 10,
                ]],
            ])
            ->assertSessionHasErrors('sale');
    }

    public function test_user_can_open_cash_and_create_multi_item_sale(): void
    {
        $user = User::factory()->create(['role' => 'seller_cashier']);
        $register = CashRegister::create([
            'name' => 'Caja Test',
            'branch_name' => 'Sucursal Centro',
            'is_active' => true,
        ]);

        $p1 = Product::create([
            'name' => 'Producto A',
            'unit_label' => 'Unidad',
            'sku' => 'SKU-A1',
            'price' => 10,
            'stock' => 20,
            'is_active' => true,
        ]);

        $p2 = Product::create([
            'name' => 'Producto B',
            'unit_label' => 'Unidad',
            'sku' => 'SKU-B1',
            'price' => 5,
            'stock' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/admin/cash/open', [
                'cash_register_id' => $register->id,
                'opening_amount' => 100,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post('/pos/sales', [
                'sale_code' => 'VTEST-0001',
                'customer_name' => 'CF',
                'items' => [
                    [
                        'product_id' => $p1->id,
                        'presentation_factor' => 1,
                        'quantity' => 2,
                        'price' => 10,
                    ],
                    [
                        'product_id' => $p2->id,
                        'presentation_factor' => 1,
                        'quantity' => 1,
                        'price' => 5,
                    ],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 15],
                    ['method' => 'card', 'amount' => 10],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sales', [
            'sale_code' => 'VTEST-0001',
            'customer_name' => 'CF',
            'total' => 25.00,
        ]);

        $this->assertDatabaseCount('sale_items', 2);
        $this->assertDatabaseCount('sale_payments', 2);

        $this->assertDatabaseHas('products', [
            'id' => $p1->id,
            'stock' => 18,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $p2->id,
            'stock' => 19,
        ]);
    }
}
