<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'name'       => fake()->unique()->bothify('device-??##'),
            'ip_address' => fake()->localIpv4(),
            'layer'      => fake()->randomElement(['core', 'distribution', 'access']),
            'type'       => fake()->randomElement(['router', 'switch', 'firewall', 'server']),
        ];
    }
}
