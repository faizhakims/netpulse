<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Admin ─────────────────────────────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'admin@netpulse.com'],
            [
                'name'      => 'Admin',
                'password'  => Hash::make('netpulse123'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['admin']);

        // ── Operator ──────────────────────────────────────────────────────────
        $operator = User::updateOrCreate(
            ['email' => 'operator@netpulse.com'],
            [
                'name'      => 'Operator',
                'password'  => Hash::make('netpulse123'),
                'role'      => 'operator',
                'is_active' => true,
            ]
        );
        $operator->syncRoles(['operator']);

        // ── Viewer ────────────────────────────────────────────────────────────
        $viewer = User::updateOrCreate(
            ['email' => 'viewer@netpulse.com'],
            [
                'name'      => 'Viewer',
                'password'  => Hash::make('netpulse123'),
                'role'      => 'viewer',
                'is_active' => true,
            ]
        );
        $viewer->syncRoles(['viewer']);

        $this->command->info('✅ Users seeded: admin, operator, viewer (all password: netpulse123)');
    }
}
