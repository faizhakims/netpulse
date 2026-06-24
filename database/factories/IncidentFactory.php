<?php

namespace Database\Factories;

use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        return [
            'device_id'  => \App\Models\Device::factory(),
            'issue'      => fake()->sentence(5),
            'status'     => fake()->randomElement(['Critical', 'Warning', 'Monitoring', 'Info']),
            'started_at' => now()->subMinutes(fake()->numberBetween(5, 120)),
            'resolved_at'=> null,
            'duration'   => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['resolved_at' => null, 'duration' => null]);
    }

    public function resolved(): static
    {
        return $this->state(function () {
            $started = now()->subHours(2);
            return [
                'started_at'  => $started,
                'resolved_at' => $started->copy()->addHour(),
                'duration'    => '1h',
            ];
        });
    }

    public function critical(): static
    {
        return $this->state(['status' => 'Critical']);
    }
}
