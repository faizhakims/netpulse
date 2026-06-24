<?php

namespace Database\Factories;

use App\Models\AlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertRuleFactory extends Factory
{
    protected $model = AlertRule::class;

    public function definition(): array
    {
        return [
            'title'           => fake()->sentence(3),
            'description'     => fake()->sentence(),
            'severity'        => fake()->randomElement(['critical', 'warning', 'info']),
            'metric_type'     => 'latency',
            'condition'       => 'gt',
            'threshold_value' => fake()->numberBetween(50, 200),
            'duration'        => fake()->randomElement(['1m', '5m', '10m', '15m', '30m']),
            'target_device_id'=> null,
            'channels'        => ['telegram'],
            'is_active'       => true,
            'sort_order'      => fake()->numberBetween(1, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function status(): static
    {
        return $this->state([
            'metric_type'     => 'status',
            'condition'       => 'is_down',
            'threshold_value' => null,
        ]);
    }

    public function critical(): static
    {
        return $this->state(['severity' => 'critical']);
    }
}
