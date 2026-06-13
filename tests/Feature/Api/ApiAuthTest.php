<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{

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


    public function test_api_logout_revokes_token(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }


    public function test_protected_api_endpoint_rejects_unauthenticated_request(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
        $this->getJson('/api/devices')->assertUnauthorized();
        $this->getJson('/api/alerts')->assertUnauthorized();
        $this->getJson('/api/incidents')->assertUnauthorized();
    }


    public function test_api_me_returns_user_profile_and_permissions(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role', 'permissions']]);
    }


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


    public function test_revoked_token_cannot_access_protected_endpoints(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('test')->plainTextToken;

        $admin->tokens()->delete();

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }


    public function test_api_login_is_rate_limited_after_many_attempts(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $this->postJson('/api/auth/login', [
                'email'    => 'nobody@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->postJson('/api/auth/login', [
            'email'    => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
