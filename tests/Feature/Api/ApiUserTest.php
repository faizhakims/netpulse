<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

class ApiUserTest extends TestCase
{
    private function apiAs($user): static
    {
        return $this->actingAs($user, 'sanctum');
    }

    // ── Authorization guard ───────────────────────────────────────────────────

    public function test_viewer_cannot_list_users_via_api(): void
    {
        $this->apiAs($this->createViewer())
            ->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_operator_cannot_list_users_via_api(): void
    {
        $this->apiAs($this->createOperator())
            ->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_admin_can_list_users(): void
    {
        User::factory()->count(5)->create();

        $this->apiAs($this->createAdmin())
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['items', 'total', 'per_page']]);
    }

    // ── Filtering ─────────────────────────────────────────────────────────────

    public function test_api_users_search_by_name(): void
    {
        User::factory()->create(['name' => 'Budi Santoso']);
        User::factory()->count(3)->create();

        $this->apiAs($this->createAdmin())
            ->getJson('/api/users?search=Budi')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    public function test_api_users_filter_by_active_status(): void
    {
        User::factory()->count(2)->create(['is_active' => true]);
        User::factory()->count(3)->create(['is_active' => false]);

        // Add 1 more active (the admin itself will be created and active)
        $admin = $this->createAdmin();

        $this->apiAs($admin)
            ->getJson('/api/users?status=active')
            ->assertOk()
            ->assertJsonPath('data.total', 3); // 2 factory + 1 admin
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_api_show_user_returns_correct_data(): void
    {
        $user = $this->createViewer(['name' => 'Specific User']);

        $this->apiAs($this->createAdmin())
            ->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Specific User')
            ->assertJsonPath('data.role', 'viewer');
    }

    public function test_api_show_user_404_for_invalid_id(): void
    {
        $this->apiAs($this->createAdmin())
            ->getJson('/api/users/99999')
            ->assertNotFound();
    }

    // ── Resource never exposes sensitive fields ────────────────────────────────

    public function test_api_user_resource_excludes_password_and_token(): void
    {
        $user = $this->createViewer();

        $response = $this->apiAs($this->createAdmin())
            ->getJson("/api/users/{$user->id}");

        $this->assertStringNotContainsString('"password"', $response->content());
        $this->assertStringNotContainsString('"remember_token"', $response->content());
    }

    public function test_api_user_resource_includes_expected_fields(): void
    {
        $user = $this->createOperator();

        $this->apiAs($this->createAdmin())
            ->getJson("/api/users/{$user->id}")
            ->assertJsonStructure(['data' => [
                'id', 'name', 'email', 'role', 'is_active',
                'last_login_at', 'created_at', 'updated_at',
            ]]);
    }
}
