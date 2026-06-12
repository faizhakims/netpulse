<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Re-seed roles/permissions (wiped by RefreshDatabase on SQLite)
        $this->seed(RolePermissionSeeder::class);

        // Clear Spatie's permission cache so role assignments are reflected immediately
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure auxiliary tables exist for tests that load pages querying them.
        // These tables are created by real migrations but may not exist in test-only
        // scenarios if migrations are pruned. Creating them idempotently is safe.
        $this->ensureAuxTablesExist();
    }

    // ── User helpers ──────────────────────────────────────────────────────────

    protected function createAdmin(?array $overrides = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->create(array_merge(['is_active' => true], $overrides));
        $user->assignRole('admin');
        return $user;
    }

    protected function createOperator(?array $overrides = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->create(array_merge(['is_active' => true], $overrides));
        $user->assignRole('operator');
        return $user;
    }

    protected function createViewer(?array $overrides = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->create(array_merge(['is_active' => true], $overrides));
        $user->assignRole('viewer');
        return $user;
    }

    protected function actingAsAdmin(): static
    {
        return $this->actingAs($this->createAdmin());
    }

    protected function actingAsOperator(): static
    {
        return $this->actingAs($this->createOperator());
    }

    protected function actingAsViewer(): static
    {
        return $this->actingAs($this->createViewer());
    }

    // ── Ensure auxiliary tables exist for page-load tests ─────────────────────

    private function ensureAuxTablesExist(): void
    {
        // device_status — used by DashboardService, IncidentService, TrafficService
        DB::statement("CREATE TABLE IF NOT EXISTS device_status (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device TEXT NOT NULL,
            ip_address TEXT,
            status TEXT NOT NULL DEFAULT 'down',
            latency_ms REAL,
            checked_at DATETIME
        )");

        // interface_traffic — used by TrafficService
        DB::statement("CREATE TABLE IF NOT EXISTS interface_traffic (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device TEXT NOT NULL,
            ip_address TEXT,
            interface_name TEXT,
            bytes_in INTEGER DEFAULT 0,
            bytes_out INTEGER DEFAULT 0,
            collected_at DATETIME
        )");

        // snmp_metrics — used by DeviceService, TrafficService
        DB::statement("CREATE TABLE IF NOT EXISTS snmp_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device TEXT NOT NULL,
            metric_name TEXT NOT NULL,
            metric_value TEXT,
            collected_at DATETIME
        )");
    }
}
