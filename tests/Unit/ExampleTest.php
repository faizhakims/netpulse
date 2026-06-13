<?php

namespace Tests\Unit;

use App\Services\AlertService;
use App\Services\UserService;
use App\Models\AlertRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lightweight service-layer unit tests.
 * These tests use the DB (in-memory SQLite) to test service logic directly
 * without going through HTTP — validating business rules in isolation.
 */
class ServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    // ── AlertService ──────────────────────────────────────────────────────────

    public function test_alert_service_create_rule_sets_description_from_title(): void
    {
        $service = app(AlertService::class);

        $rule = $service->createRule([
            'title'           => 'My Rule',
            'metric_type'     => 'latency',
            'condition'       => 'gt',
            'threshold_value' => 50.0,
            'duration'        => '5m',
            'severity'        => 'warning',
            'channels'        => ['telegram'],
            'is_active'       => true,
        ]);

        $this->assertEquals('My Rule', $rule->description);
    }

    public function test_alert_service_create_rule_auto_increments_sort_order(): void
    {
        $service = app(AlertService::class);

        AlertRule::factory()->create(['sort_order' => 100]);

        $rule = $service->createRule([
            'title'           => 'New Rule',
            'metric_type'     => 'latency',
            'condition'       => 'gt',
            'threshold_value' => 80.0,
            'duration'        => '5m',
            'severity'        => 'info',
            'channels'        => ['telegram'],
            'is_active'       => true,
        ]);

        $this->assertGreaterThan(100, $rule->sort_order);
    }

    public function test_alert_service_toggle_rule_flips_is_active(): void
    {
        $service = app(AlertService::class);
        $rule    = AlertRule::factory()->create(['is_active' => true]);

        $toggled = $service->toggleRule($rule);
        $this->assertFalse($toggled->is_active);

        $toggled2 = $service->toggleRule($toggled);
        $this->assertTrue($toggled2->is_active);
    }

    public function test_alert_service_duplicate_rule_appends_copy_to_title(): void
    {
        $service  = app(AlertService::class);
        $original = AlertRule::factory()->create(['title' => 'Original']);

        $copy = $service->duplicateRule($original);

        $this->assertEquals('Original (Copy)', $copy->title);
        $this->assertNotEquals($original->id, $copy->id);
        $this->assertDatabaseHas('alert_rules', ['title' => 'Original (Copy)']);
    }

    public function test_alert_service_delete_rule_removes_from_db(): void
    {
        $service = app(AlertService::class);
        $rule    = AlertRule::factory()->create();
        $id      = $rule->id;

        $service->deleteRule($rule);

        $this->assertDatabaseMissing('alert_rules', ['id' => $id]);
    }

    // ── UserService ───────────────────────────────────────────────────────────

    public function test_user_service_create_user_assigns_spatie_role(): void
    {
        $service = app(UserService::class);

        $user = $service->createUser([
            'name'     => 'Test User',
            'email'    => 'testuser@example.com',
            'password' => 'Password1!',
            'role'     => 'operator',
        ]);

        $this->assertTrue($user->hasRole('operator'));
        $this->assertTrue($user->is_active);
    }

    public function test_user_service_create_user_hashes_password(): void
    {
        $service = app(UserService::class);

        $user = $service->createUser([
            'name'     => 'Hash Test',
            'email'    => 'hash@example.com',
            'password' => 'PlainText1!',
            'role'     => 'viewer',
        ]);

        $this->assertNotEquals('PlainText1!', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('PlainText1!', $user->password));
    }

    public function test_user_service_cannot_delete_the_last_admin(): void
    {
        $service = app(UserService::class);

        // Create only one admin
        $onlyAdmin = \App\Models\User::factory()->create(['is_active' => true]);
        $onlyAdmin->assignRole('admin');

        // Simulate acting as someone else (we'll mock Auth::id via actingAs)
        $actor = \App\Models\User::factory()->create(['is_active' => true]);
        $actor->assignRole('admin');

        $this->actingAs($actor);

        // Try to delete the only admin — actor is different so self-delete guard won't fire
        // But since onlyAdmin IS the last admin, it should throw
        // After deleting actor, onlyAdmin remains
        $actor->delete();

        // Now onlyAdmin is the only admin
        // Acting as some other non-admin for the HTTP call won't work for service test
        // Test via exception directly
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete the last admin account.');

        // We need to pass any non-self user as actor to bypass self-delete guard
        // Patch Auth::id to return a different ID
        \Illuminate\Support\Facades\Auth::setUser(
            \App\Models\User::factory()->create(['is_active' => true])
        );

        $service->deleteUser($onlyAdmin);
    }

    public function test_user_service_toggle_throws_exception_for_self(): void
    {
        $service = app(UserService::class);
        $user    = $this->createAdmin();

        $this->actingAs($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot deactivate your own account.');

        $service->toggleUser($user);
    }
}
