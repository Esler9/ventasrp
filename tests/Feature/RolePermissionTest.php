<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_cashier_cannot_access_user_management_screen(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller_cashier',
        ]);

        $this->actingAs($seller)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_owner_manager_can_access_user_management_screen(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner_manager',
        ]);

        $this->actingAs($owner)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_inertia_shares_permissions_and_role_key(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller_cashier',
        ]);

        $this->actingAs($seller)
            ->get('/pos')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.role_key', 'seller_cashier')
                ->where('auth.user.role_label', 'Vendedor/Cajero')
                ->has('auth.user.permissions')
            );
    }

    public function test_root_redirects_to_role_default_home(): void
    {
        $warehouse = User::factory()->create([
            'role' => 'warehouse',
        ]);

        $this->actingAs($warehouse)
            ->get('/')
            ->assertRedirect('/admin/products');
    }
}
