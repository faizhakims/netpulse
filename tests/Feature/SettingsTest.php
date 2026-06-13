<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    // ── General Settings ──────────────────────────────────────────────────────

    public function test_admin_can_save_general_settings(): void
    {
        $this->actingAsAdmin()
            ->postJson('/settings/general', [
                'site_name'     => 'NetPulse Test',
                'site_timezone' => 'Asia/Jakarta',
                'date_format'   => 'd/m/Y',
                'site_language' => 'id',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertEquals('NetPulse Test', SystemSetting::get('site_name'));
        $this->assertEquals('Asia/Jakarta', SystemSetting::get('site_timezone'));
    }

    public function test_general_settings_are_persisted_with_correct_group(): void
    {
        $this->actingAsAdmin()
            ->postJson('/settings/general', [
                'site_name'     => 'GroupTest',
                'site_timezone' => 'Asia/Jakarta',
                'date_format'   => 'd/m/Y',
                'site_language' => 'id',
            ]);

        $this->assertDatabaseHas('system_settings', [
            'key'   => 'site_name',
            'value' => 'GroupTest',
            'group' => 'general',
        ]);
    }

    public function test_operator_cannot_save_general_settings(): void
    {
        $this->actingAsOperator()
            ->postJson('/settings/general', [
                'site_name'     => 'Hacked',
                'site_timezone' => 'UTC',
                'date_format'   => 'd/m/Y',
                'site_language' => 'en',
            ])
            ->assertForbidden();
    }

    public function test_viewer_cannot_save_general_settings(): void
    {
        $this->actingAsViewer()
            ->postJson('/settings/general', [
                'site_name'     => 'Hacked',
                'site_timezone' => 'UTC',
                'date_format'   => 'd/m/Y',
                'site_language' => 'en',
            ])
            ->assertForbidden();
    }

    public function test_guest_cannot_save_general_settings(): void
    {
        $this->postJson('/settings/general', [
                'site_name'     => 'Hacked',
                'site_timezone' => 'UTC',
                'date_format'   => 'd/m/Y',
                'site_language' => 'en',
            ])
            ->assertUnauthorized();
    }

    // ── Security Settings ─────────────────────────────────────────────────────

    public function test_admin_can_save_security_settings(): void
    {
        $this->actingAsAdmin()
            ->postJson('/settings/security', [
                'session_timeout'    => 30,
                'max_login_attempts' => 5,
                'lockout_duration'   => 15,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertEquals(30, (int) SystemSetting::get('session_timeout'));
    }

    public function test_security_settings_are_persisted_with_correct_group(): void
    {
        $this->actingAsAdmin()
            ->postJson('/settings/security', [
                'session_timeout'    => 60,
                'max_login_attempts' => 5,
                'lockout_duration'   => 10,
            ]);

        $this->assertDatabaseHas('system_settings', [
            'key'   => 'session_timeout',
            'group' => 'security',
        ]);
    }

    public function test_operator_cannot_save_security_settings(): void
    {
        $this->actingAsOperator()
            ->postJson('/settings/security', [
                'session_timeout'    => 30,
                'max_login_attempts' => 5,
                'lockout_duration'   => 15,
            ])
            ->assertForbidden();
    }

    public function test_viewer_cannot_save_security_settings(): void
    {
        $this->actingAsViewer()
            ->postJson('/settings/security', [
                'session_timeout'    => 30,
                'max_login_attempts' => 5,
                'lockout_duration'   => 15,
            ])
            ->assertForbidden();
    }

    // ── Settings Index (Admin only) ───────────────────────────────────────────

    public function test_admin_can_access_settings_page(): void
    {
        $this->actingAsAdmin()
            ->get('/settings')
            ->assertOk();
    }

    public function test_operator_cannot_access_settings_page(): void
    {
        $this->actingAsOperator()
            ->get('/settings')
            ->assertForbidden();
    }

    public function test_viewer_cannot_access_settings_page(): void
    {
        $this->actingAsViewer()
            ->get('/settings')
            ->assertForbidden();
    }

    // ── Profile Update (any authenticated user) ───────────────────────────────

    public function test_admin_can_update_own_profile_name(): void
    {
        $admin = $this->createAdmin(['name' => 'Old Admin Name']);

        $this->actingAs($admin)
            ->postJson('/settings/profile', [
                'name'  => 'New Admin Name',
                'email' => $admin->email,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('users', [
            'id'   => $admin->id,
            'name' => 'New Admin Name',
        ]);
    }

    public function test_operator_can_update_own_profile(): void
    {
        $operator = $this->createOperator();

        $this->actingAs($operator)
            ->postJson('/settings/profile', [
                'name'  => 'New Operator Name',
                'email' => $operator->email,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_viewer_can_update_own_profile(): void
    {
        $viewer = $this->createViewer();

        $this->actingAs($viewer)
            ->postJson('/settings/profile', [
                'name'  => 'New Viewer Name',
                'email' => $viewer->email,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = $this->createAdmin(['password' => bcrypt('CurrentPass1!')]);

        $this->actingAs($user)
            ->postJson('/settings/profile', [
                'name'                      => $user->name,
                'email'                     => $user->email,
                'current_password'          => 'CurrentPass1!',
                'new_password'              => 'BrandNew2@',
                'new_password_confirmation' => 'BrandNew2@',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        // New password must validate
        $this->assertTrue(
            Hash::check('BrandNew2@', $user->fresh()->password)
        );
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $user = $this->createAdmin(['password' => bcrypt('CurrentPass1!')]);

        $this->actingAs($user)
            ->postJson('/settings/profile', [
                'name'                      => $user->name,
                'email'                     => $user->email,
                'current_password'          => 'WrongPassword!',
                'new_password'              => 'BrandNew2@',
                'new_password_confirmation' => 'BrandNew2@',
            ])
            ->assertStatus(422);

        // Password must remain unchanged
        $this->assertTrue(
            Hash::check('CurrentPass1!', $user->fresh()->password)
        );
    }

    public function test_profile_update_without_password_change_does_not_alter_password(): void
    {
        $user = $this->createAdmin(['password' => bcrypt('StayTheSame1!')]);

        $this->actingAs($user)
            ->postJson('/settings/profile', [
                'name'  => 'Updated Name',
                'email' => $user->email,
                // no new_password field
            ])
            ->assertOk();

        $this->assertTrue(
            Hash::check('StayTheSame1!', $user->fresh()->password)
        );
    }

    // ── System Info (Admin only) ──────────────────────────────────────────────
    // system-info uses MySQL information_schema — SQLite-incompatible.
    // Test that authorization works (not 403/401) rather than response content.

    public function test_admin_can_access_system_info(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/settings/system-info');

        // Must not be forbidden or unauthenticated
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_operator_cannot_access_system_info(): void
    {
        $this->actingAsOperator()
            ->getJson('/settings/system-info')
            ->assertForbidden();
    }

    public function test_viewer_cannot_access_system_info(): void
    {
        $this->actingAsViewer()
            ->getJson('/settings/system-info')
            ->assertForbidden();
    }

    // ── Clear Logs (Admin only) ───────────────────────────────────────────────

    public function test_admin_can_clear_logs(): void
    {
        $this->actingAsAdmin()
            ->postJson('/settings/clear-logs')
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_operator_cannot_clear_logs(): void
    {
        $this->actingAsOperator()
            ->postJson('/settings/clear-logs')
            ->assertForbidden();
    }

    public function test_viewer_cannot_clear_logs(): void
    {
        $this->actingAsViewer()
            ->postJson('/settings/clear-logs')
            ->assertForbidden();
    }
}
