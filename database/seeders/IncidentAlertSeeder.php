<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Incident;
use App\Models\AlertRule;
use App\Models\AlertChannel;
use App\Models\AlertHistory;

class IncidentAlertSeeder extends Seeder
{
    public function run(): void
    {
        // ── Alert Channels ────────────────────────────────────────────────────
        AlertChannel::firstOrCreate(['type' => 'telegram'], [
            'is_active' => true,
            'config'    => [
                'token'   => '1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                'chat_id' => '-100123456789',
            ],
        ]);

        AlertChannel::firstOrCreate(['type' => 'email'], [
            'is_active' => true,
            'config'    => [
                'host'     => 'smtp.mailgun.org',
                'port'     => 587,
                'username' => 'alerts@netpulse.io',
                'password' => '',
            ],
        ]);

        // ── Alert Rules ───────────────────────────────────────────────────────
        $rules = [
            [
                'severity'        => 'critical',
                'title'           => 'High Latency Alert',
                'description'     => 'If Latency > 100ms for 5 mins',
                'metric_type'     => 'latency',
                'condition'       => 'gt',
                'threshold_value' => 100,
                'duration'        => '5m',
                'target_device'   => null,
                'channels'        => ['telegram', 'email'],
                'is_active'       => true,
                'sort_order'      => 1,
            ],
            [
                'severity'        => 'warning',
                'title'           => 'Device Offline',
                'description'     => 'If Device Status is DOWN',
                'metric_type'     => 'status',
                'condition'       => 'is_down',
                'threshold_value' => null,
                'duration'        => '1m',
                'target_device'   => null,
                'channels'        => ['telegram'],
                'is_active'       => true,
                'sort_order'      => 2,
            ],
            [
                'severity'        => 'warning',
                'title'           => 'High Bandwidth Usage',
                'description'     => 'If Bandwidth > 90% for 15 mins',
                'metric_type'     => 'bandwidth',
                'condition'       => 'gt',
                'threshold_value' => 90,
                'duration'        => '15m',
                'target_device'   => null,
                'channels'        => ['email'],
                'is_active'       => false,
                'sort_order'      => 3,
            ],
        ];

        foreach ($rules as $r) {
            AlertRule::firstOrCreate(['title' => $r['title']], $r);
        }

        // ── Sample Incidents ──────────────────────────────────────────────────
        // Active incidents use real device names that match device_status entries
        // so the auto-resolve logic can properly evaluate them
        $active = [
            ['device' => 'main-router',   'ip_address' => '192.168.99.1', 'issue' => 'Connection lost – no traffic',  'status' => 'Critical',   'started_at' => now()->subHours(1)->subMinutes(12)],
            ['device' => 'router-kantor', 'ip_address' => '192.168.99.3', 'issue' => 'Packet loss > 15%',             'status' => 'Warning',    'started_at' => now()->subMinutes(45)],
            ['device' => 'openWRT',       'ip_address' => '192.168.99.4', 'issue' => 'Latency spike – 120ms',         'status' => 'Monitoring', 'started_at' => now()->subMinutes(2)->subSeconds(14)],
            ['device' => 'Switch-1',      'ip_address' => '192.168.99.5', 'issue' => 'Interface flapping',            'status' => 'Info',       'started_at' => now()->subSeconds(18)],
        ];

        foreach ($active as $row) {
            Incident::firstOrCreate(
                ['device' => $row['device'], 'resolved_at' => null],
                $row
            );
        }

        $resolved = [
            ['device' => 'main-router',   'ip_address' => '192.168.99.1', 'issue' => 'Packet loss',        'status' => 'Warning',   'started_at' => now()->subHours(3), 'resolved_at' => now()->subHours(2)->subMinutes(58), 'duration' => '2m'],
            ['device' => 'Switch-2',      'ip_address' => '192.168.99.6', 'issue' => 'High CPU usage',     'status' => 'Warning',   'started_at' => now()->subHours(4), 'resolved_at' => now()->subHours(3)->subMinutes(55), 'duration' => '5m'],
            ['device' => 'openWRT',       'ip_address' => '192.168.99.4', 'issue' => 'Latency spike',      'status' => 'Monitoring','started_at' => now()->subHours(5), 'resolved_at' => now()->subHours(4)->subMinutes(59), 'duration' => '1m'],
            ['device' => 'router-kantor', 'ip_address' => '192.168.99.3', 'issue' => 'Interface down',     'status' => 'Critical',  'started_at' => now()->subHours(6), 'resolved_at' => now()->subHours(5)->subMinutes(59)->subSeconds(30), 'duration' => '30s'],
            ['device' => 'main-router',   'ip_address' => '192.168.99.1', 'issue' => 'Connection lost',    'status' => 'Critical',  'started_at' => now()->subHours(8), 'resolved_at' => now()->subHours(7), 'duration' => '1h'],
        ];

        foreach ($resolved as $row) {
            Incident::create($row);
        }

        // ── Sample Alert History ──────────────────────────────────────────────
        $rule = AlertRule::first();
        $historyData = [
            ['channel' => 'telegram', 'recipient' => '-100123456789', 'status' => 'sent',   'message' => 'CRITICAL: Core-Router-01 is DOWN',              'sent_at' => now()->subHours(1)],
            ['channel' => 'email',    'recipient' => 'alerts@netpulse.io', 'status' => 'sent',   'message' => 'WARNING: High Latency (142ms) on Link-NYC', 'sent_at' => now()->subHours(3)],
            ['channel' => 'telegram', 'recipient' => '-100123456789', 'status' => 'failed',  'message' => 'CRITICAL: Database sync failed on DB-02',        'sent_at' => now()->subDay()->addHours(1)],
        ];

        foreach ($historyData as $h) {
            AlertHistory::create(array_merge($h, ['alert_rule_id' => $rule?->id]));
        }
    }
}
