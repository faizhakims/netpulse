<?php

namespace Tests\Feature\Api;

use App\Models\Incident;
use Tests\TestCase;

class ApiIncidentTest extends TestCase
{
    private function apiAs($user): static
    {
        return $this->actingAs($user, 'sanctum');
    }


    public function test_api_list_incidents_returns_paginated_structure(): void
    {
        Incident::factory()->count(5)->active()->create();

        $this->apiAs($this->createAdmin())
            ->getJson('/api/incidents')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['items', 'total', 'per_page']]);
    }

    public function test_api_incidents_filter_active_only(): void
    {
        Incident::factory()->count(3)->active()->create();
        Incident::factory()->count(2)->resolved()->create();

        $this->apiAs($this->createAdmin())
            ->getJson('/api/incidents?status=active')
            ->assertOk()
            ->assertJsonPath('data.total', 3);
    }

    public function test_api_incidents_filter_resolved_only(): void
    {
        Incident::factory()->count(2)->active()->create();
        Incident::factory()->count(4)->resolved()->create();

        $this->apiAs($this->createAdmin())
            ->getJson('/api/incidents?status=resolved')
            ->assertOk()
            ->assertJsonPath('data.total', 4);
    }

    public function test_api_incidents_search_by_device_name(): void
    {
        Incident::factory()->create(['device_id' => \App\Models\Device::factory()->create(['name' => 'unique-target-device'])->id]);
        Incident::factory()->count(3)->create();

        $this->apiAs($this->createAdmin())
            ->getJson('/api/incidents?search=unique-target')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }


    public function test_api_show_incident_returns_correct_fields(): void
    {
        $incident = Incident::factory()->active()->create([
            'device_id' => \App\Models\Device::factory()->create(['name' => 'edge-switch-1'])->id,
            'status' => 'Critical',
        ]);

        $this->apiAs($this->createAdmin())
            ->getJson("/api/incidents/{$incident->id}")
            ->assertOk()
            ->assertJsonPath('data.device', 'edge-switch-1')
            ->assertJsonPath('data.status', 'Critical')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_api_show_incident_404_for_invalid_id(): void
    {
        $this->apiAs($this->createAdmin())
            ->getJson('/api/incidents/99999')
            ->assertNotFound();
    }


    public function test_api_admin_can_manually_resolve_active_incident(): void
    {
        $incident = Incident::factory()->active()->create();

        $this->apiAs($this->createAdmin())
            ->putJson("/api/incidents/{$incident->id}", ['action' => 'resolve'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        $this->assertNotNull($incident->fresh()->resolved_at);
    }

    public function test_api_operator_can_resolve_incident(): void
    {
        $incident = Incident::factory()->active()->create();

        $this->apiAs($this->createOperator())
            ->putJson("/api/incidents/{$incident->id}", ['action' => 'resolve'])
            ->assertOk();
    }

    public function test_api_resolving_already_resolved_incident_returns_conflict(): void
    {
        $incident = Incident::factory()->resolved()->create();

        $this->apiAs($this->createAdmin())
            ->putJson("/api/incidents/{$incident->id}", ['action' => 'resolve'])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_api_viewer_cannot_resolve_incidents(): void
    {
        $incident = Incident::factory()->active()->create();

        $this->apiAs($this->createViewer())
            ->putJson("/api/incidents/{$incident->id}", ['action' => 'resolve'])
            ->assertForbidden();
    }


    public function test_api_incident_resource_has_all_required_fields(): void
    {
        $incident = Incident::factory()->active()->create();

        $this->apiAs($this->createAdmin())
            ->getJson("/api/incidents/{$incident->id}")
            ->assertJsonStructure(['data' => [
                'id', 'device', 'ip_address', 'issue',
                'status', 'is_active', 'duration',
                'started_at', 'resolved_at',
            ]]);
    }
}
