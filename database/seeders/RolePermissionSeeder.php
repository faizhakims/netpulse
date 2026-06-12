<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Define all permissions ────────────────────────────────────────────
        $permissions = [
            // Dashboard
            'view dashboard',

            // Devices
            'view devices',
            'manage devices',

            // Traffic
            'view traffic',

            // Alerts
            'view alerts',
            'manage alerts',

            // Incidents
            'view incidents',
            'manage incidents',

            // Logs
            'view logs',

            // Settings
            'manage settings',

            // Users
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Create roles and assign permissions ───────────────────────────────

        // Admin — full access
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        // Operator — manage devices & incidents, view alerts, no users/settings
        $operator = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        $operator->syncPermissions([
            'view dashboard',
            'view devices',
            'manage devices',
            'view traffic',
            'view alerts',
            'view incidents',
            'manage incidents',
            'view logs',
        ]);

        // Viewer — read-only
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'view dashboard',
            'view devices',
            'view traffic',
            'view alerts',
            'view incidents',
            'view logs',
        ]);

        $this->command->info('✅ Roles and permissions seeded successfully.');
    }
}
