<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Define all permissions ─────────────────────────────────────────────
        $permissions = [
            // Dashboard
            'view dashboard',

            // Users management (Admin only)
            'manage users',
            'create users',
            'edit users',
            'delete users',

            // Devices
            'manage devices',
            'view devices',
            'create devices',
            'edit devices',
            'delete devices',

            // Alerts
            'manage alerts',
            'view alerts',
            'create alerts',
            'edit alerts',
            'delete alerts',

            // Incidents
            'manage incidents',
            'view incidents',
            'create incidents',
            'edit incidents',
            'delete incidents',

            // Settings
            'manage settings',
            'view settings',
            'edit settings',

            // Traffic monitoring (read-only)
            'view traffic',

            // Logs (read-only)
            'view logs',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Create roles and assign permissions ────────────────────────────────

        // Admin - Full access
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        // Operator - Manage devices and incidents, view alerts, cannot manage users
        $operatorRole = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        $operatorRole->givePermissionTo([
            'view dashboard',
            'manage devices',
            'view devices',
            'create devices',
            'edit devices',
            'delete devices',
            'manage incidents',
            'view incidents',
            'create incidents',
            'edit incidents',
            'delete incidents',
            'view alerts',
            'view traffic',
            'view logs',
        ]);

        // Viewer - Read-only access
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo([
            'view dashboard',
            'view devices',
            'view traffic',
            'view alerts',
            'view incidents',
            'view logs',
            'view settings',
        ]);

        // ── Assign admin role to existing admin user ───────────────────────────
        $adminUser = User::where('email', 'admin@netpulse.com')->first();
        if ($adminUser) {
            $adminUser->assignRole('admin');
        }

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Roles: admin, operator, viewer');
        $this->command->info('Admin user assigned admin role.');
    }
}
