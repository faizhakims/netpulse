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
        if (app()->isProduction()) {
            $this->command->error('❌ UserSeeder ditolak: tidak boleh dijalankan di environment production!');
            $this->command->info('   Gunakan php artisan tinker untuk membuat admin pertama.');
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $adminPassword    = bin2hex(random_bytes(8));
        $operatorPassword = bin2hex(random_bytes(8));
        $viewerPassword   = bin2hex(random_bytes(8));

        $admin = User::updateOrCreate(
            ['email' => 'admin@netpulse.local'],
            [
                'name'      => 'Admin',
                'password'  => Hash::make($adminPassword),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['admin']);

        $operator = User::updateOrCreate(
            ['email' => 'operator@netpulse.local'],
            [
                'name'      => 'Operator',
                'password'  => Hash::make($operatorPassword),
                'role'      => 'operator',
                'is_active' => true,
            ]
        );
        $operator->syncRoles(['operator']);

        $viewer = User::updateOrCreate(
            ['email' => 'viewer@netpulse.local'],
            [
                'name'      => 'Viewer',
                'password'  => Hash::make($viewerPassword),
                'role'      => 'viewer',
                'is_active' => true,
            ]
        );
        $viewer->syncRoles(['viewer']);

        $this->command->info('✅ Users seeded (development only). Catat password berikut — hanya ditampilkan sekali:');
        $this->command->table(
            ['Email', 'Role', 'Password (catat sekarang!)'],
            [
                ['admin@netpulse.local',    'admin',    $adminPassword],
                ['operator@netpulse.local', 'operator', $operatorPassword],
                ['viewer@netpulse.local',   'viewer',   $viewerPassword],
            ]
        );
        $this->command->warn('⚠️  Segera ubah password ini melalui halaman Settings setelah login pertama!');
    }
}
