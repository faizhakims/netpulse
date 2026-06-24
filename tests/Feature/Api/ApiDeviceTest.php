<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiDeviceTest extends TestCase
{
    private function apiAs($user): static
    {
        return $this->actingAs($user, 'sanctum');
    }

    /**
     * Seed a device_status row for tests that need to find a device.
     */
    private function seedDevice(string $name = 'test-router', string $ip = '192.168.1.1', string $status = 'up'): int
    {
        $device = \App\Models\Device::firstOrCreate(
            ['name' => $name],
            ['ip_address' => $ip]
        );

        DB::table('device_status')->insert([
            'device_id'  => $device->id,
            'device'     => $name,
            'ip_address' => $ip,
            'status'     => $status,
            'latency_ms' => 12.5,
            'checked_at' => now()->toDateTimeString(),
        ]);

        return $device->id;
    }


    public function test_api_list_devices_returns_paginated_structure(): void
    {
        $this->seedDevice('router-alpha', '10.0.0.1');
        $this->seedDevice('router-beta',  '10.0.0.2');

        $this->apiAs($this->createAdmin())
            ->getJson('/api/devices')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['items', 'total', 'per_page', 'current_page', 'last_page']]);
    }

    public function test_api_list_devices_total_reflects_seeded_data(): void
    {
        $this->seedDevice('sw-01', '10.1.0.1');
        $this->seedDevice('sw-02', '10.1.0.2');
        $this->seedDevice('sw-03', '10.1.0.3');

        $response = $this->apiAs($this->createAdmin())
            ->getJson('/api/devices')
            ->assertOk();

        $this->assertGreaterThanOrEqual(3, $response->json('data.total'));
    }

    public function test_api_devices_search_filter_by_name(): void
    {
        $this->seedDevice('unique-target-switch', '10.5.0.1');
        $this->seedDevice('another-device',       '10.5.0.2');

        $this->apiAs($this->createAdmin())
            ->getJson('/api/devices?search=unique-target')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    public function test_api_devices_search_filter_by_ip(): void
    {
        $this->seedDevice('device-ip-test', '172.31.99.88');
        $this->seedDevice('device-other',   '10.0.0.1');

        $this->apiAs($this->createAdmin())
            ->getJson('/api/devices?search=172.31.99')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }


    public function test_viewer_can_list_devices_via_api(): void
    {
        $this->seedDevice();

        $this->apiAs($this->createViewer())
            ->getJson('/api/devices')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_operator_can_list_devices_via_api(): void
    {
        $this->seedDevice();

        $this->apiAs($this->createOperator())
            ->getJson('/api/devices')
            ->assertOk();
    }

    public function test_unauthenticated_cannot_list_devices(): void
    {
        $this->getJson('/api/devices')
            ->assertUnauthorized();
    }


    public function test_api_show_device_returns_correct_data(): void
    {
        $id = $this->seedDevice('core-router', '192.168.100.1');

        $this->apiAs($this->createAdmin())
            ->getJson("/api/devices/core-router")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.device.name', 'core-router');
    }

    public function test_api_show_nonexistent_device_returns_404(): void
    {
        $this->apiAs($this->createAdmin())
            ->getJson('/api/devices/nonexistent-device-xyz')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_viewer_can_show_device_via_api(): void
    {
        $id = $this->seedDevice('edge-switch');

        $this->apiAs($this->createViewer())
            ->getJson("/api/devices/edge-switch")
            ->assertOk();
    }


    public function test_viewer_cannot_delete_device_via_api(): void
    {
        $id = $this->seedDevice('deletable-device');

        $this->apiAs($this->createViewer())
            ->deleteJson("/api/devices/deletable-device")
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_viewer_cannot_ping_via_api(): void
    {
        $this->apiAs($this->createViewer())
            ->postJson('/api/devices', [
                'device' => 'some-router',
                'action' => 'ping',
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_delete_device(): void
    {
        $this->deleteJson('/api/devices/some-device')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_cannot_ping_via_api(): void
    {
        $this->postJson('/api/devices', ['device' => 'router', 'action' => 'ping'])
            ->assertUnauthorized();
    }


    public function test_ping_action_requires_device_field(): void
    {
        $this->apiAs($this->createOperator())
            ->postJson('/api/devices', ['action' => 'ping']) // missing 'device'
            ->assertStatus(422);
    }

    public function test_ping_action_rejects_invalid_action(): void
    {
        $this->apiAs($this->createOperator())
            ->postJson('/api/devices', [
                'device' => 'some-router',
                'action' => 'explode', // invalid action
            ])
            ->assertStatus(422);
    }


    public function test_api_device_resource_never_exposes_raw_internal_columns(): void
    {
        $this->seedDevice('resource-test-device', '10.10.10.10');

        $response = $this->apiAs($this->createAdmin())
            ->getJson('/api/devices')
            ->assertOk();

        $items = $response->json('data.items');
        $this->assertNotEmpty($items);

        $first = $items[0];
        $this->assertArrayHasKey('name',       $first);  // 'device' column mapped to 'name' in resource
        $this->assertArrayHasKey('ip_address', $first);
        $this->assertArrayHasKey('status',     $first);
        $this->assertArrayNotHasKey('id', $first);  // raw DB IDs should not be exposed
    }
}
