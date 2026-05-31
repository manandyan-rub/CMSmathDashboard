<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CmsSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_own_profile_inside_cms(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Old Admin',
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->patchJson('/cms-api/profile', [
            'name' => 'Math Owner',
            'email' => 'owner@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('item.name', 'Math Owner')
            ->assertJsonPath('item.email', 'owner@example.com')
            ->assertJsonPath('item.email_verified_at', null);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Math Owner',
            'email' => 'owner@example.com',
        ]);
    }

    public function test_admin_can_update_own_password_inside_cms(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($admin)->putJson('/cms-api/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
    }
}
