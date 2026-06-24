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

        $this->seed(RolePermissionSeeder::class);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->ensureAuxTablesExist();
    }


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


    private function ensureAuxTablesExist(): void
    {
        DB::statement("CREATE TABLE IF NOT EXISTS device_status (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id INTEGER,
            device TEXT NOT NULL,
            ip_address TEXT,
            status TEXT NOT NULL DEFAULT 'down',
            latency_ms REAL,
            checked_at DATETIME
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS interface_traffic (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id INTEGER,
            device TEXT NOT NULL,
            ip_address TEXT,
            interface_name TEXT,
            bytes_in INTEGER DEFAULT 0,
            bytes_out INTEGER DEFAULT 0,
            collected_at DATETIME
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS snmp_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id INTEGER,
            device TEXT NOT NULL,
            metric_name TEXT NOT NULL,
            metric_value TEXT,
            collected_at DATETIME
        )");
    }
}
