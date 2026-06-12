<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_login_page_is_accessible_to_guests(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $this->actingAsAdmin()->get('/login')->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = $this->createAdmin(['password' => bcrypt('secret123')]);

        $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $admin = $this->createAdmin();

        $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $this->post('/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_is_rate_limited_after_many_attempts(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $this->post('/login', ['email' => 'x@x.com', 'password' => 'wrong']);
        }
        // 11th attempt should be rate-limited (429)
        $this->post('/login', ['email' => 'x@x.com', 'password' => 'wrong'])
             ->assertStatus(429);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    // ── Unauthorized access ───────────────────────────────────────────────────

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guest_cannot_access_settings(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_guest_cannot_access_devices(): void
    {
        $this->get('/device')->assertRedirect('/login');
    }

    public function test_inactive_user_is_denied_access(): void
    {
        $inactive = $this->createAdmin(['is_active' => false]);

        $this->actingAs($inactive)
             ->get('/dashboard')
             ->assertRedirect('/login');
    }
}
