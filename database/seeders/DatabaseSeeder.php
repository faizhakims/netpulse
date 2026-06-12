<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class, // Must run before UserSeeder
            UserSeeder::class,
            IncidentAlertSeeder::class,
        ]);
    }
}
