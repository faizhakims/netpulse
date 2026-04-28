<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@netpulse.com'],
            [
                'name'       => 'Admin',
                'email'      => 'admin@netpulse.com',
                'password'   => Hash::make('netpulse123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
