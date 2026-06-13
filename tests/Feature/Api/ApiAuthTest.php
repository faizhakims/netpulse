<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_api_login_returns_token_for_valid_credentials(): void
    {
        $admin = $this->createAdmin(['password' => bcrypt('secret123')]);

        $this->postJson('/api/auth/login', [
            'email'       => $admin->email,
            'password'    => 'secret123',
            'device_name' => 'phpunit',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);
    }

    public function test_api_login_fails_with_wrong_password(): void
    {
        $admin = $this->createAdmin();

        $this->postJson('/api/auth/login', [
            'email'    => $admin->email,
            'password' => 'wrong-password',
        ])
        ->assertStatus(401)
        ->assertJsonPath('success', false);
    }

    public function test_api_login_rejected_for_inactive_user(): void
    {
        $inactive = $this->createAdmin(['is_active' => false]);

        $this->postJson('/api/auth/login', [
            'email'    => $inactive->email,
            'password' => 'password',
        ])
        ->assertStatus(403)
        ->assertJsonPath('success', false);
    }

    public function test_api_login_requires_email_and_password(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_api_logout_revokes_token(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        // Token is now revoked — the personal_access_token row was deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ── Protected endpoints require auth ──────────────────────────────────────

    public function test_protected_api_endpoint_rejects_unauthenticated_request(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
        $this->getJson('/api/devices')->assertUnauthorized();
        $this->getJson('/api/alerts')->assertUnauthorized();
        $this->getJson('/api/incidents')->assertUnauthorized();
    }

    // ── Me ────────────────────────────────────────────────────────────────────

    public function test_api_me_returns_user_profile_and_permissions(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role', 'permissions']]);
    }

    // ── Response never exposes sensitive fields ────────────────────────────────

    public function test_api_login_response_never_contains_password(): void
    {
        $admin = $this->createAdmin(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => $admin->email,
            'password' => 'secret123',
        ]);

        $this->assertStringNotContainsString('"password"', $response->content());
        $this->assertStringNotContainsString('"remember_token"', $response->content());
    }

    // ── Token security ────────────────────────────────────────────────────

    public function test_revoked_token_cannot_access_protected_endpoints(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('test')->plainTextToken;

        // Revoke the token directly in the database
        // (If we use the /api/auth/logout endpoint first, Laravel caches the user
        // in the AuthManager for the remainder of the test lifecycle).
        $admin->tokens()->delete();

        // Reusing the revoked token must return 401
        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    // ── Rate limiting ────────────────────────────────────────────────────

    public function test_api_login_is_rate_limited_after_many_attempts(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $this->postJson('/api/auth/login', [
                'email'    => 'nobody@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 11th+ attempt should be rate-limited (429)
        $this->postJson('/api/auth/login', [
            'email'    => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
