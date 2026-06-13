<?php

namespace Tests\Feature;

use Tests\TestCase;

class RbacTest extends TestCase
{

    public function test_all_roles_can_reach_dashboard(): void
    {
        foreach ([$this->createAdmin(), $this->createOperator(), $this->createViewer()] as $user) {
            $response = $this->actingAs($user)->get('/dashboard');
            $this->assertNotEquals(403, $response->status(),
                "Role {$user->currentRoleName()} should not be forbidden from dashboard");
            $this->assertNotEquals(302, $response->status(),
                "Role {$user->currentRoleName()} should not be redirected from dashboard");
        }
    }


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


    public function test_admin_and_operator_can_ping_device(): void
    {
        foreach ([$this->createAdmin(), $this->createOperator()] as $user) {
            $response = $this->actingAs($user)
                ->postJson('/device/ping', ['device' => 'test-device']);

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
        $response = $this->actingAsOperator()
            ->deleteJson('/device/nonexistent-device/delete');

        $this->assertNotEquals(403, $response->status(),
            'Operators with manage devices permission should not be forbidden from delete');
    }


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
