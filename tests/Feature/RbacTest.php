<?php

namespace Tests\Feature;

use Tests\TestCase;

class RbacTest extends TestCase
{
    // ── Dashboard ─────────────────────────────────────────────────────────────
    // The dashboard page calls DashboardService which uses MySQL FIELD() ordering
    // incompatible with SQLite. We keep the assertNotEquals guard only for this
    // specific page — all other pages are SQLite-safe and use assertOk().

    public function test_all_roles_can_reach_dashboard(): void
    {
        foreach ([$this->createAdmin(), $this->createOperator(), $this->createViewer()] as $user) {
            $response = $this->actingAs($user)->get('/dashboard');
            // Must not be a 403 (forbidden) or redirect-to-login (302)
            $this->assertNotEquals(403, $response->status(),
                "Role {$user->currentRoleName()} should not be forbidden from dashboard");
            $this->assertNotEquals(302, $response->status(),
                "Role {$user->currentRoleName()} should not be redirected from dashboard");
        }
    }

    // ── Settings ─────────────────────────────────────────────────────────────

    public function test_admin_can_access_settings(): void
    {
        $this->actingAsAdmin()->get('/settings')->assertOk();
    }

    public function test_operator_cannot_access_settings(): void
    {
        $this->actingAsOperator()->get('/settings')->assertForbidden();
    }

    public function test_viewer_cannot_access_settings(): void
    {
        $this->actingAsViewer()->get('/settings')->assertForbidden();
    }

    // ── Devices ───────────────────────────────────────────────────────────────

    public function test_admin_and_operator_can_ping_device(): void
    {
        foreach ([$this->createAdmin(), $this->createOperator()] as $user) {
            $response = $this->actingAs($user)
                ->postJson('/device/ping', ['device' => 'test-device']);

            // Ping reaches the service layer (200 or 5xx from unreachable host)
            // but must never be 403 or 401.
            $this->assertNotEquals(403, $response->status(),
                "Role {$user->currentRoleName()} should not be forbidden from ping");
            $this->assertNotEquals(401, $response->status(),
                "Role {$user->currentRoleName()} should be authenticated");
        }
    }

    public function test_viewer_cannot_ping_device(): void
    {
        $this->actingAsViewer()
            ->postJson('/device/ping', ['device' => 'test-device'])
            ->assertForbidden();
    }

    public function test_viewer_cannot_delete_device(): void
    {
        $this->actingAsViewer()
            ->deleteJson('/device/some-router/delete')
            ->assertForbidden();
    }

    public function test_operator_has_manage_devices_permission(): void
    {
        // Per web routes, operators are in the 'manage devices' group.
        // Deleting a non-existent device returns an error response from DeviceService
        // but must NOT be a 403. We verify authorization passes.
        $response = $this->actingAsOperator()
            ->deleteJson('/device/nonexistent-device/delete');

        $this->assertNotEquals(403, $response->status(),
            'Operators with manage devices permission should not be forbidden from delete');
    }

    // ── Alerts ────────────────────────────────────────────────────────────────
    // AlertController::index() uses FIELD() MySQL-specific ordering incompatible
    // with SQLite. We assert not 403 / not 302 only — 500 from FIELD() is a
    // SQLite test environment limitation, not a permission failure.

    public function test_admin_can_reach_alert_page(): void
    {
        $response = $this->actingAsAdmin()->get('/alert');
        $this->assertNotEquals(403, $response->status(), 'Admin should not be forbidden from alert page');
        $this->assertNotEquals(302, $response->status(), 'Admin should not be redirected from alert page');
    }

    public function test_operator_can_reach_alert_page(): void
    {
        $response = $this->actingAsOperator()->get('/alert');
        $this->assertNotEquals(403, $response->status(), 'Operator should not be forbidden from alert page');
        $this->assertNotEquals(302, $response->status(), 'Operator should not be redirected from alert page');
    }

    public function test_viewer_can_reach_alert_page(): void
    {
        $response = $this->actingAsViewer()->get('/alert');
        $this->assertNotEquals(403, $response->status(), 'Viewer should not be forbidden from alert page');
        $this->assertNotEquals(302, $response->status(), 'Viewer should not be redirected from alert page');
    }

    public function test_operator_can_view_but_not_manage_alerts(): void
    {
        // Operator cannot create alert rules (manage permission)
        $this->actingAsOperator()
            ->postJson('/alert/rules', [])
            ->assertForbidden();
    }

    public function test_viewer_cannot_manage_alerts(): void
    {
        $this->actingAsViewer()
            ->postJson('/alert/rules', [])
            ->assertForbidden();
    }

    // ── Incidents ─────────────────────────────────────────────────────────────
    // IncidentService::getIncidentsData() uses FIELD() MySQL-specific ordering
    // incompatible with SQLite. Assert not 403 / not 302 only.

    public function test_all_roles_can_reach_incidents_page(): void
    {
        foreach ([$this->createAdmin(), $this->createOperator(), $this->createViewer()] as $user) {
            $response = $this->actingAs($user)->get('/incidents');
            $this->assertNotEquals(403, $response->status(),
                "Role {$user->currentRoleName()} should not be forbidden from incidents");
            $this->assertNotEquals(302, $response->status(),
                "Role {$user->currentRoleName()} should not be redirected from incidents");
        }
    }

    // ── User Management ───────────────────────────────────────────────────────

    public function test_only_admin_can_store_users(): void
    {
        $payload = [
            'name'     => 'New User',
            'email'    => 'new@example.com',
            'role'     => 'viewer',
            'password' => 'Password1!',
        ];

        $this->actingAsAdmin()->postJson('/settings/users', $payload)->assertOk();
        $this->actingAsOperator()->postJson('/settings/users', $payload)->assertForbidden();
        $this->actingAsViewer()->postJson('/settings/users', $payload)->assertForbidden();
    }

    public function test_only_admin_can_update_users(): void
    {
        $user = $this->createViewer();

        $this->actingAsOperator()
            ->putJson("/settings/users/{$user->id}", [
                'name'  => 'Hacked Name',
                'email' => $user->email,
                'role'  => 'admin',
            ])
            ->assertForbidden();

        $this->actingAsViewer()
            ->putJson("/settings/users/{$user->id}", [
                'name'  => 'Hacked Name',
                'email' => $user->email,
                'role'  => 'admin',
            ])
            ->assertForbidden();
    }

    public function test_only_admin_can_delete_users(): void
    {
        $user = $this->createViewer();

        $this->actingAsOperator()
            ->deleteJson("/settings/users/{$user->id}")
            ->assertForbidden();

        $this->actingAsViewer()
            ->deleteJson("/settings/users/{$user->id}")
            ->assertForbidden();
    }
}
