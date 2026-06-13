<?php

namespace Tests\Feature\Api;

use App\Models\AlertRule;
use Tests\TestCase;

class ApiAlertTest extends TestCase
{
    private function apiAs($user): static
    {
        return $this->actingAs($user, 'sanctum');
    }


    public function test_api_list_alerts_returns_paginated_structure(): void
    {
        AlertRule::factory()->count(5)->create();
        $admin = $this->createAdmin();

        $this->apiAs($admin)
            ->getJson('/api/alerts')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['items', 'total', 'per_page', 'current_page', 'last_page']]);
    }

    public function test_api_alerts_filter_by_active_status(): void
    {
        AlertRule::factory()->count(3)->create(['is_active' => true]);
        AlertRule::factory()->count(2)->inactive()->create();

        $this->apiAs($this->createAdmin())
            ->getJson('/api/alerts?status=active')
            ->assertOk()
            ->assertJsonPath('data.total', 3);
    }

    public function test_api_alerts_filter_by_severity(): void
    {
        AlertRule::factory()->critical()->count(2)->create();
        AlertRule::factory()->create(['severity' => 'warning']);

        $this->apiAs($this->createAdmin())
            ->getJson('/api/alerts?severity=critical')
            ->assertOk()
            ->assertJsonPath('data.total', 2);
    }

    public function test_api_alerts_search_by_title(): void
    {
        AlertRule::factory()->create(['title' => 'Unique Searchable Title XYZ']);
        AlertRule::factory()->count(3)->create();

        $this->apiAs($this->createAdmin())
            ->getJson('/api/alerts?search=Unique+Searchable')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }


    public function test_api_show_alert_returns_correct_rule(): void
    {
        $rule = AlertRule::factory()->create(['title' => 'Specific Rule']);

        $this->apiAs($this->createAdmin())
            ->getJson("/api/alerts/{$rule->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Specific Rule')
            ->assertJsonPath('data.id', $rule->id);
    }

    public function test_api_show_alert_returns_404_for_missing_id(): void
    {
        $this->apiAs($this->createAdmin())
            ->getJson('/api/alerts/9999')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }


    public function test_api_store_alert_rule_and_persists_to_db(): void
    {
        $this->apiAs($this->createAdmin())
            ->postJson('/api/alerts', [
                'title'           => 'API Created Rule',
                'metric_type'     => 'latency',
                'condition'       => 'gt',
                'threshold_value' => 80,
                'duration'        => '5m',
                'severity'        => 'warning',
                'channels'        => ['telegram'],
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('alert_rules', ['title' => 'API Created Rule']);
    }

    public function test_api_store_rejects_invalid_condition_for_status_metric(): void
    {
        $response = $this->apiAs($this->createAdmin())
            ->postJson('/api/alerts', [
                'title'       => 'Bad',
                'metric_type' => 'status',
                'condition'   => 'gt',        // invalid for status metric
                'duration'    => '5m',
                'severity'    => 'warning',
                'channels'    => ['telegram'],
            ]);

        $response->assertStatus(422);
        $body = $response->json();
        $this->assertTrue(
            ($body['success'] ?? null) === false || ($body['ok'] ?? null) === false,
            'Expected a failure indicator in response body'
        );
    }


    public function test_api_update_alert_rule(): void
    {
        $rule = AlertRule::factory()->create();

        $this->apiAs($this->createAdmin())
            ->putJson("/api/alerts/{$rule->id}", [
                'title'           => 'Updated via API',
                'metric_type'     => 'latency',
                'condition'       => 'lt',
                'threshold_value' => 20,
                'duration'        => '10m',
                'severity'        => 'info',
                'channels'        => ['email'],
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated via API');

        $this->assertDatabaseHas('alert_rules', ['id' => $rule->id, 'title' => 'Updated via API']);
    }


    public function test_api_delete_alert_rule(): void
    {
        $rule = AlertRule::factory()->create();

        $this->apiAs($this->createAdmin())
            ->deleteJson("/api/alerts/{$rule->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('alert_rules', ['id' => $rule->id]);
    }


    public function test_viewer_cannot_create_alert_via_api(): void
    {
        $this->apiAs($this->createViewer())
            ->postJson('/api/alerts', ['title' => 'Unauthorized'])
            ->assertForbidden();
    }

    public function test_viewer_can_read_alerts_via_api(): void
    {
        AlertRule::factory()->count(2)->create();

        $this->apiAs($this->createViewer())
            ->getJson('/api/alerts')
            ->assertOk();
    }


    public function test_alert_resource_includes_condition_label(): void
    {
        $rule = AlertRule::factory()->create([
            'metric_type'     => 'latency',
            'condition'       => 'gt',
            'threshold_value' => 100,
            'duration'        => '5m',
        ]);

        $this->apiAs($this->createAdmin())
            ->getJson("/api/alerts/{$rule->id}")
            ->assertJsonPath('data.condition_label', 'If Latency > 100ms for 5m');
    }


    public function test_operator_cannot_create_alert_via_api(): void
    {
        $this->apiAs($this->createOperator())
            ->postJson('/api/alerts', [
                'title'           => 'Unauthorized',
                'metric_type'     => 'latency',
                'condition'       => 'gt',
                'threshold_value' => 50,
                'duration'        => '5m',
                'severity'        => 'info',
                'channels'        => ['telegram'],
            ])
            ->assertForbidden();
    }

    public function test_operator_cannot_update_alert_via_api(): void
    {
        $rule = AlertRule::factory()->create();

        $this->apiAs($this->createOperator())
            ->putJson("/api/alerts/{$rule->id}", [
                'title'           => 'Hijacked',
                'metric_type'     => 'latency',
                'condition'       => 'gt',
                'threshold_value' => 50,
                'duration'        => '5m',
                'severity'        => 'info',
                'channels'        => ['telegram'],
            ])
            ->assertForbidden();
    }

    public function test_operator_cannot_delete_alert_via_api(): void
    {
        $rule = AlertRule::factory()->create();

        $this->apiAs($this->createOperator())
            ->deleteJson("/api/alerts/{$rule->id}")
            ->assertForbidden();
    }


    public function test_api_update_nonexistent_alert_returns_404(): void
    {
        $this->apiAs($this->createAdmin())
            ->putJson('/api/alerts/99999', [
                'title'           => 'Ghost',
                'metric_type'     => 'latency',
                'condition'       => 'gt',
                'threshold_value' => 50,
                'duration'        => '5m',
                'severity'        => 'info',
                'channels'        => ['telegram'],
            ])
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_api_delete_nonexistent_alert_returns_404(): void
    {
        $this->apiAs($this->createAdmin())
            ->deleteJson('/api/alerts/99999')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
