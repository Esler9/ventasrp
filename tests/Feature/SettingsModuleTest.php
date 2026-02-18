<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_manager_can_open_settings_screen(): void
    {
        $user = User::factory()->create(['role' => 'owner_manager']);

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertOk();
    }

    public function test_seller_cannot_open_settings_screen(): void
    {
        $user = User::factory()->create(['role' => 'seller_cashier']);

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_owner_manager_can_update_brand_colors(): void
    {
        $user = User::factory()->create(['role' => 'owner_manager']);
        AppSetting::current();

        $this->actingAs($user)
            ->post('/admin/settings', [
                'primary_color' => '#112233',
                'secondary_color' => '#334455',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('app_settings', [
            'primary_color' => '#112233',
            'secondary_color' => '#334455',
        ]);
    }
}
