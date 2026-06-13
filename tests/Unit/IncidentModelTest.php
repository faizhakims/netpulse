<?php

namespace Tests\Unit;

use App\Models\Incident;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Unit tests for Incident model helpers.
 * Uses Laravel TestCase (with in-memory SQLite) so that model casts
 * ($casts = ['started_at' => 'datetime']) are properly applied.
 */
class IncidentModelTest extends TestCase
{

    public function test_is_active_returns_true_when_resolved_at_is_null(): void
    {
        $incident = Incident::factory()->make(['resolved_at' => null]);
        $this->assertTrue($incident->isActive());
    }

    public function test_is_active_returns_false_when_resolved_at_is_set(): void
    {
        $incident = Incident::factory()->make(['resolved_at' => Carbon::now()]);
        $this->assertFalse($incident->isActive());
    }


    public function test_badge_class_for_critical_status(): void
    {
        $incident = Incident::factory()->make(['status' => 'Critical']);
        $this->assertEquals('badge-critical', $incident->badgeClass());
    }

    public function test_badge_class_for_warning_status(): void
    {
        $incident = Incident::factory()->make(['status' => 'Warning']);
        $this->assertEquals('badge-high', $incident->badgeClass());
    }

    public function test_badge_class_for_monitoring_status(): void
    {
        $incident = Incident::factory()->make(['status' => 'Monitoring']);
        $this->assertEquals('badge-normal', $incident->badgeClass());
    }

    public function test_badge_class_for_info_status(): void
    {
        $incident = Incident::factory()->make(['status' => 'Info']);
        $this->assertEquals('badge-info', $incident->badgeClass());
    }

    public function test_badge_class_for_unknown_status_returns_info(): void
    {
        $incident = Incident::factory()->make(['status' => 'SomethingRandom']);
        $this->assertEquals('badge-info', $incident->badgeClass());
    }

    public function test_badge_class_is_case_sensitive(): void
    {
        $incident = Incident::factory()->make(['status' => 'critical']);
        $this->assertEquals('badge-info', $incident->badgeClass());
    }


    public function test_display_duration_returns_stored_value_when_present(): void
    {
        $incident = Incident::factory()->make(['duration' => '2h 15m', 'started_at' => null]);
        $this->assertEquals('2h 15m', $incident->displayDuration());
    }


    public function test_display_duration_returns_dash_when_started_at_is_null(): void
    {
        $incident = Incident::factory()->make([
            'duration'    => null,
            'started_at'  => null,
            'resolved_at' => null,
        ]);
        $this->assertEquals('—', $incident->displayDuration());
    }

    public function test_display_duration_returns_seconds_for_sub_minute_duration(): void
    {
        $incident = Incident::factory()->make([
            'duration'    => null,
            'started_at'  => Carbon::now()->subSeconds(45),
            'resolved_at' => Carbon::now(),
        ]);
        $this->assertEquals('45s', $incident->displayDuration());
    }

    public function test_display_duration_returns_minutes_for_exact_minute(): void
    {
        $incident = Incident::factory()->make([
            'duration'    => null,
            'started_at'  => Carbon::now()->subSeconds(120), // exactly 2 minutes
            'resolved_at' => Carbon::now(),
        ]);
        $this->assertEquals('2m', $incident->displayDuration());
    }

    public function test_display_duration_returns_minutes_and_seconds(): void
    {
        $incident = Incident::factory()->make([
            'duration'    => null,
            'started_at'  => Carbon::now()->subSeconds(150), // 2m 30s
            'resolved_at' => Carbon::now(),
        ]);
        $this->assertEquals('2m 30s', $incident->displayDuration());
    }

    public function test_display_duration_returns_hours_and_minutes(): void
    {
        $incident = Incident::factory()->make([
            'duration'    => null,
            'started_at'  => Carbon::now()->subSeconds(5400), // 1h 30m
            'resolved_at' => Carbon::now(),
        ]);
        $this->assertEquals('1h 30m', $incident->displayDuration());
    }

    public function test_display_duration_returns_exact_hours_when_no_minutes(): void
    {
        $incident = Incident::factory()->make([
            'duration'    => null,
            'started_at'  => Carbon::now()->subSeconds(7200), // exactly 2h
            'resolved_at' => Carbon::now(),
        ]);
        $this->assertEquals('2h', $incident->displayDuration());
    }

    public function test_display_duration_returns_dash_for_negative_duration(): void
    {
        $incident = Incident::factory()->make([
            'duration'    => null,
            'started_at'  => Carbon::now()->addHour(),
            'resolved_at' => Carbon::now(),
        ]);
        $this->assertEquals('—', $incident->displayDuration());
    }

    public function test_display_duration_for_active_incident_uses_now_as_end(): void
    {
        $incident = Incident::factory()->make([
            'duration'    => null,
            'started_at'  => Carbon::now()->subMinutes(5),
            'resolved_at' => null,
        ]);
        $result = $incident->displayDuration();
        $this->assertNotEquals('—', $result);
        $this->assertMatchesRegularExpression('/\d/', $result);
    }

    public function test_display_duration_returns_zero_seconds_for_instant(): void
    {
        $now = Carbon::now();
        $incident = Incident::factory()->make([
            'duration'    => null,
            'started_at'  => $now,
            'resolved_at' => $now->copy(),
        ]);
        $this->assertEquals('0s', $incident->displayDuration());
    }
}
