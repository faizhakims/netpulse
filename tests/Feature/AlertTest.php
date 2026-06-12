<?php

namespace Tests\Feature;

use App\Models\AlertRule;
use Tests\TestCase;

class AlertTest extends TestCase
{
    // ── Store Rule ────────────────────────────────────────────────────────────

    public function test_admin_can_create_latency_alert_rule(): void
    {
        $this->actingAsAdmin()
            ->postJson('/alert/rules', [
                'title'           => 'High Latency Alert',
                'metric_type'     => 'latency',
                'condition'       => 'gt',
                'threshold_value' => 100,
                'duration'        => '5m',
                'severity'        => 'critical',
                'channels'        => ['telegram'],
                'is_active'       => true,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('alert_rules', ['title' => 'High Latency Alert']);
    }

    public function test_admin_can_create_status_alert_rule_without_threshold(): void
    {
        $this->actingAsAdmin()
            ->postJson('/alert/rules', [
                'title'       => 'Device Down',
                'metric_type' => 'status',
                'condition'   => 'is_down',
                'duration'    => '1m',
                'severity'    => 'critical',
                'channels'    => ['telegram'],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('alert_rules', [
            'title'           => 'Device Down',
            'threshold_value' => null,
        ]);
    }

    public function test_creating_rule_requires_at_least_one_channel(): void
    {
        $this->actingAsAdmin()
            ->postJson('/alert/rules', [
                'title'           => 'No Channel Rule',
                'metric_type'     => 'latency',
                'condition'       => 'gt',
                'threshold_value' => 50,
                'duration'        => '5m',
                'severity'        => 'warning',
                'channels'        => [],
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_status_metric_rejects_numeric_condition(): void
    {
        $this->actingAsAdmin()
            ->postJson('/alert/rules', [
                'title'           => 'Bad Rule',
                'metric_type'     => 'status',
                'condition'       => 'gt',    // invalid for status metric
                'threshold_value' => 100,
                'duration'        => '5m',
                'severity'        => 'critical',
                'channels'        => ['telegram'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_numeric_metric_requires_threshold_value(): void
    {
        $this->actingAsAdmin()
            ->postJson('/alert/rules', [
                'title'           => 'Missing Threshold',
                'metric_type'     => 'latency',
                'condition'       => 'gt',
                'threshold_value' => null,    // required for numeric condition
                'duration'        => '5m',
                'severity'        => 'warning',
                'channels'        => ['telegram'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    // ── Update Rule ───────────────────────────────────────────────────────────

    public function test_admin_can_update_alert_rule(): void
    {
        $rule = AlertRule::factory()->create(['title' => 'Old Title']);

        $this->actingAsAdmin()
            ->putJson("/alert/rules/{$rule->id}", [
                'title'           => 'Updated Title',
                'metric_type'     => 'latency',
                'condition'       => 'gt',
                'threshold_value' => 150,
                'duration'        => '10m',
                'severity'        => 'warning',
                'channels'        => ['email'],
                'is_active'       => true,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('alert_rules', ['id' => $rule->id, 'title' => 'Updated Title']);
    }

    // ── Toggle Rule ───────────────────────────────────────────────────────────

    public function test_admin_can_toggle_alert_rule(): void
    {
        $rule = AlertRule::factory()->create(['is_active' => true]);

        $this->actingAsAdmin()
            ->postJson("/alert/rules/{$rule->id}/toggle")
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('alert_rules', ['id' => $rule->id, 'is_active' => false]);
    }

    public function test_toggle_reverses_on_second_call(): void
    {
        $rule = AlertRule::factory()->create(['is_active' => true]);
        $admin = $this->createAdmin();

        $this->actingAs($admin)->postJson("/alert/rules/{$rule->id}/toggle");
        $this->actingAs($admin)->postJson("/alert/rules/{$rule->id}/toggle")
            ->assertJsonPath('is_active', true);
    }

    // ── Delete Rule ───────────────────────────────────────────────────────────

    public function test_admin_can_delete_alert_rule(): void
    {
        $rule = AlertRule::factory()->create();

        $this->actingAsAdmin()
            ->deleteJson("/alert/rules/{$rule->id}")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('alert_rules', ['id' => $rule->id]);
    }

    public function test_operator_cannot_create_alert_rules(): void
    {
        $this->actingAsOperator()
            ->postJson('/alert/rules', ['title' => 'Unauthorized'])
            ->assertForbidden();
    }

    // ── Duplicate Rule ────────────────────────────────────────────────────────

    public function test_admin_can_duplicate_alert_rule(): void
    {
        $rule = AlertRule::factory()->create(['title' => 'Original Rule']);

        $this->actingAsAdmin()
            ->postJson("/alert/rules/{$rule->id}/duplicate")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('alert_rules', ['title' => 'Original Rule (Copy)']);
    }
}
