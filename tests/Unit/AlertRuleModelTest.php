<?php

namespace Tests\Unit;

use App\Models\AlertRule;
use PHPUnit\Framework\TestCase;

/**
 * Pure in-memory unit tests for AlertRule::conditionLabel().
 * No DB hits — no Laravel app needed beyond model instantiation.
 */
class AlertRuleModelTest extends TestCase
{
    private function makeRule(array $attrs): AlertRule
    {
        $rule = new AlertRule();
        foreach ($attrs as $key => $value) {
            $rule->$key = $value;
        }
        return $rule;
    }

    // ── Status metric ─────────────────────────────────────────────────────────

    public function test_condition_label_for_status_down_metric(): void
    {
        $rule = $this->makeRule([
            'metric_type' => 'status',
            'condition'   => 'is_down',
            'duration'    => '5m',
        ]);

        $label = $rule->conditionLabel();
        $this->assertStringContainsString('5m', $label);
        $this->assertStringContainsString('up', strtolower($label));
    }

    public function test_condition_label_for_status_up_metric(): void
    {
        $rule = $this->makeRule([
            'metric_type' => 'status',
            'condition'   => 'is_up',
            'duration'    => '10m',
        ]);

        $label = $rule->conditionLabel();
        $this->assertStringContainsString('10m', $label);
        $this->assertStringContainsString('UP', $label);
    }

    // ── Latency metric ────────────────────────────────────────────────────────

    public function test_condition_label_for_latency_gt_condition(): void
    {
        $rule = $this->makeRule([
            'metric_type'     => 'latency',
            'condition'       => 'gt',
            'threshold_value' => 100,
            'duration'        => '5m',
        ]);

        $label = $rule->conditionLabel();
        $this->assertEquals('If Latency > 100ms for 5m', $label);
    }

    public function test_condition_label_for_latency_lt_condition(): void
    {
        $rule = $this->makeRule([
            'metric_type'     => 'latency',
            'condition'       => 'lt',
            'threshold_value' => 20,
            'duration'        => '1m',
        ]);

        $label = $rule->conditionLabel();
        $this->assertEquals('If Latency < 20ms for 1m', $label);
    }

    public function test_condition_label_for_latency_eq_condition(): void
    {
        $rule = $this->makeRule([
            'metric_type'     => 'latency',
            'condition'       => 'eq',
            'threshold_value' => 0,
            'duration'        => '5m',
        ]);

        $label = $rule->conditionLabel();
        $this->assertEquals('If Latency = 0ms for 5m', $label);
    }

    // ── Bandwidth metric ──────────────────────────────────────────────────────

    public function test_condition_label_for_bandwidth_metric(): void
    {
        $rule = $this->makeRule([
            'metric_type'     => 'bandwidth',
            'condition'       => 'gt',
            'threshold_value' => 1000,
            'duration'        => '10m',
        ]);

        $label = $rule->conditionLabel();
        $this->assertEquals('If Bandwidth > 1000Mbps for 10m', $label);
    }

    public function test_condition_label_for_bandwidth_lt_condition(): void
    {
        $rule = $this->makeRule([
            'metric_type'     => 'bandwidth',
            'condition'       => 'lt',
            'threshold_value' => 100,
            'duration'        => '5m',
        ]);

        $this->assertStringContainsString('Bandwidth', $rule->conditionLabel());
        $this->assertStringContainsString('Mbps', $rule->conditionLabel());
    }

    // ── Packet loss metric ────────────────────────────────────────────────────

    public function test_condition_label_for_packet_loss_metric(): void
    {
        $rule = $this->makeRule([
            'metric_type'     => 'packet_loss',
            'condition'       => 'gt',
            'threshold_value' => 5,
            'duration'        => '5m',
        ]);

        $label = $rule->conditionLabel();
        $this->assertEquals('If Packet loss > 5% for 5m', $label);
    }

    public function test_condition_label_uses_default_duration_when_null(): void
    {
        $rule = $this->makeRule([
            'metric_type'     => 'latency',
            'condition'       => 'gt',
            'threshold_value' => 50,
            'duration'        => null, // should fall back to '5m'
        ]);

        $label = $rule->conditionLabel();
        $this->assertStringContainsString('5m', $label);
    }

    // ── Unknown metric type falls back to default ─────────────────────────────

    public function test_condition_label_unknown_metric_type_has_no_unit(): void
    {
        $rule = $this->makeRule([
            'metric_type'     => 'custom_metric',
            'condition'       => 'gt',
            'threshold_value' => 10,
            'duration'        => '5m',
        ]);

        $label = $rule->conditionLabel();
        // Falls through to default '' unit — should not crash
        $this->assertStringContainsString('>', $label);
        $this->assertStringContainsString('10', $label);
    }
}
