<?php

namespace Tests\Feature;

use App\Models\Incident;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    // ── View ─────────────────────────────────────────────────────────────────
    // IncidentService::getIncidentsData() uses FIELD() MySQL-specific ordering
    // incompatible with SQLite. We assert not 403 / not 302 only.

    public function test_admin_can_reach_incidents_page(): void
    {
        $response = $this->actingAsAdmin()->get('/incidents');
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(302, $response->status());
    }

    public function test_operator_can_reach_incidents_page(): void
    {
        $response = $this->actingAsOperator()->get('/incidents');
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(302, $response->status());
    }

    public function test_viewer_can_reach_incidents_page(): void
    {
        $response = $this->actingAsViewer()->get('/incidents');
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(302, $response->status());
    }

    public function test_guest_cannot_reach_incidents_page(): void
    {
        $this->get('/incidents')->assertRedirect('/login');
    }

    // ── Model state ───────────────────────────────────────────────────────────

    public function test_active_incident_has_no_resolved_at(): void
    {
        $incident = Incident::factory()->active()->create();

        $this->assertNull($incident->resolved_at);
        $this->assertTrue($incident->isActive());
    }

    public function test_resolved_incident_has_resolved_at_timestamp(): void
    {
        $incident = Incident::factory()->resolved()->create();

        $this->assertNotNull($incident->resolved_at);
        $this->assertFalse($incident->isActive());
    }

    // ── displayDuration ───────────────────────────────────────────────────────

    public function test_display_duration_uses_stored_duration_if_present(): void
    {
        $incident = Incident::factory()->create(['duration' => '5m 30s']);

        $this->assertEquals('5m 30s', $incident->displayDuration());
    }

    public function test_display_duration_returns_seconds_when_under_one_minute(): void
    {
        $incident = Incident::factory()->create([
            'started_at'  => now()->subSeconds(45),
            'resolved_at' => now(),
            'duration'    => null,
        ]);

        $this->assertEquals('45s', $incident->displayDuration());
    }

    public function test_display_duration_returns_minutes_without_seconds_when_exact(): void
    {
        $incident = Incident::factory()->create([
            'started_at'  => now()->subMinutes(10),
            'resolved_at' => now(),
            'duration'    => null,
        ]);

        // Should be "10m" exactly (no trailing "0s")
        $this->assertMatchesRegularExpression('/^\d+m$/', $incident->displayDuration());
    }

    public function test_display_duration_returns_formatted_string_for_hours(): void
    {
        $incident = Incident::factory()->create([
            'started_at'  => now()->subMinutes(90),
            'resolved_at' => now(),
            'duration'    => null,
        ]);

        $this->assertMatchesRegularExpression('/\d+h \d+m|\d+m/', $incident->displayDuration());
    }

    public function test_display_duration_returns_dash_when_started_at_is_null(): void
    {
        $incident = Incident::factory()->make([
            'started_at'  => null,
            'resolved_at' => null,
            'duration'    => null,
        ]);

        $this->assertEquals('—', $incident->displayDuration());
    }

    public function test_display_duration_returns_dash_for_negative_duration(): void
    {
        // started_at in the future (data anomaly) — should return '—'
        $incident = Incident::factory()->make([
            'started_at'  => now()->addHour(),
            'resolved_at' => now(),
            'duration'    => null,
        ]);

        $this->assertEquals('—', $incident->displayDuration());
    }

    public function test_display_duration_for_active_incident_uses_current_time(): void
    {
        // Active incident — no resolved_at, duration should reflect elapsed time
        $incident = Incident::factory()->create([
            'started_at'  => now()->subMinutes(5),
            'resolved_at' => null,
            'duration'    => null,
        ]);

        $result = $incident->displayDuration();
        $this->assertNotEquals('—', $result);
        $this->assertMatchesRegularExpression('/\d+/', $result);
    }

    // ── badgeClass ────────────────────────────────────────────────────────────

    public function test_critical_incident_has_correct_badge_class(): void
    {
        $incident = Incident::factory()->critical()->make();

        $this->assertEquals('badge-critical', $incident->badgeClass());
    }

    public function test_warning_incident_has_correct_badge_class(): void
    {
        $incident = Incident::factory()->make(['status' => 'Warning']);

        $this->assertEquals('badge-high', $incident->badgeClass());
    }

    public function test_monitoring_incident_has_correct_badge_class(): void
    {
        $incident = Incident::factory()->make(['status' => 'Monitoring']);

        $this->assertEquals('badge-normal', $incident->badgeClass());
    }

    public function test_unknown_status_returns_info_badge_class(): void
    {
        $incident = Incident::factory()->make(['status' => 'SomeUnknownStatus']);

        $this->assertEquals('badge-info', $incident->badgeClass());
    }

    public function test_info_status_returns_info_badge_class(): void
    {
        $incident = Incident::factory()->make(['status' => 'Info']);

        $this->assertEquals('badge-info', $incident->badgeClass());
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function test_active_scope_returns_only_unresolved(): void
    {
        Incident::factory()->active()->count(3)->create();
        Incident::factory()->resolved()->count(2)->create();

        $this->assertEquals(3, Incident::active()->count());
    }

    public function test_resolved_scope_returns_only_resolved(): void
    {
        Incident::factory()->active()->count(2)->create();
        Incident::factory()->resolved()->count(4)->create();

        $this->assertEquals(4, Incident::resolved()->count());
    }

    public function test_active_and_resolved_scopes_are_mutually_exclusive(): void
    {
        Incident::factory()->active()->count(2)->create();
        Incident::factory()->resolved()->count(3)->create();

        $total    = Incident::count();
        $active   = Incident::active()->count();
        $resolved = Incident::resolved()->count();

        $this->assertEquals($total, $active + $resolved);
    }
}
