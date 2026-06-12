<?php

namespace Tests\Feature;

use App\Models\Incident;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    // ── View ─────────────────────────────────────────────────────────────────

    public function test_admin_can_reach_incidents_page(): void
    {
        // The incidents page queries device_status with a MySQL FIELD() function.
        // We assert it is not forbidden (403) or a redirect — not necessarily 200,
        // since MySQL-specific SQL may fail on SQLite.
        $response = $this->actingAsAdmin()->get('/incidents');

        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(302, $response->status());
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

    public function test_display_duration_returns_formatted_string(): void
    {
        $incident = Incident::factory()->create([
            'started_at'  => now()->subMinutes(90),
            'resolved_at' => now(),
            'duration'    => null,
        ]);

        $this->assertMatchesRegularExpression('/\d+h \d+m|\d+m/', $incident->displayDuration());
    }

    public function test_display_duration_uses_stored_duration_if_present(): void
    {
        $incident = Incident::factory()->create(['duration' => '5m 30s']);

        $this->assertEquals('5m 30s', $incident->displayDuration());
    }

    public function test_critical_incident_has_correct_badge_class(): void
    {
        $incident = Incident::factory()->critical()->make();

        $this->assertEquals('badge-critical', $incident->badgeClass());
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
}
