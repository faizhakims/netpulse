<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AlertRule;
use App\Models\AlertHistory;
use App\Models\AlertChannel;
use Illuminate\Support\Facades\DB;

class CheckAlertRules extends Command
{
    protected $signature   = 'alerts:check';
    protected $description = 'Evaluasi semua threshold rules terhadap data device terbaru, kirim notifikasi jika terpenuhi.';

    public function handle(): void
    {
        $rules = AlertRule::where('is_active', true)->get();

        if ($rules->isEmpty()) {
            $this->info('Tidak ada rule aktif.');
            return;
        }

        // Ambil status terbaru per device
        $latestDevices = DB::table('device_status')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('device_status')->groupBy('device');
            })
            ->get()
            ->keyBy('device');

        // Ambil rata-rata latency per device (MariaDB compatible)
        $avgLatency = DB::table('device_status')
            ->select('device', DB::raw('AVG(latency_ms) as avg_latency'))
            ->groupBy('device')
            ->get()
            ->keyBy('device');

        foreach ($rules as $rule) {
            $targets = $this->resolveTargets($rule, $latestDevices);

            foreach ($targets as $device) {
                $triggered = $this->evaluate($rule, $device, $avgLatency);

                if ($triggered) {
                    $this->line("  ⚡ Rule [{$rule->title}] triggered untuk device [{$device->device}]");
                    $this->sendAlert($rule, $device);

                    // Update trigger count & last_triggered_at
                    $rule->increment('trigger_count');
                    $rule->last_triggered_at = now();
                    $rule->saveQuietly();

                    // Buat incident otomatis jika belum ada yang active
                    $this->createIncidentIfNeeded($rule, $device);
                }
            }
        }

        $this->info('Alert check selesai — ' . now()->format('H:i:s'));
    }

    // ── Tentukan device yang dicek ──────────────────────────────────────────
    private function resolveTargets(AlertRule $rule, $allDevices): array
    {
        if (empty($rule->target_device) || strtolower($rule->target_device) === 'all') {
            return $allDevices->values()->all();
        }

        $match = $allDevices->get($rule->target_device);
        return $match ? [$match] : [];
    }

    // ── Evaluasi kondisi rule terhadap satu device ──────────────────────────
    private function evaluate(AlertRule $rule, $device, $avgLatency): bool
    {
        $metric    = $rule->metric_type ?? 'latency';
        $condition = $rule->condition   ?? 'gt';
        $threshold = (float) ($rule->threshold_value ?? 0);

        switch ($metric) {
            case 'status':
                if ($condition === 'is_down') return strtolower($device->status) === 'down';
                if ($condition === 'is_up')   return strtolower($device->status) === 'up';
                return false;

            case 'latency':
                $val = (float) ($avgLatency->get($device->device)?->avg_latency ?? $device->latency_ms ?? 0);
                return $this->compare($val, $condition, $threshold);

            case 'packet_loss':
                // Hitung % down dari riwayat 10 terakhir
                $recent = DB::table('device_status')
                    ->where('device', $device->device)
                    ->orderByDesc('id')->limit(10)->get();
                $lossRate = $recent->isEmpty() ? 0
                    : ($recent->where('status', 'down')->count() / $recent->count()) * 100;
                return $this->compare($lossRate, $condition, $threshold);

            case 'bandwidth':
            case 'cpu':
            case 'memory':
                // Ambil dari snmp_metrics jika tersedia
                $snmpVal = DB::table('snmp_metrics')
                    ->where('device', $device->device)
                    ->where('metric_name', $metric)
                    ->orderByDesc('id')->value('metric_value');
                if ($snmpVal === null) return false;
                return $this->compare((float) $snmpVal, $condition, $threshold);
        }

        return false;
    }

    private function compare(float $val, string $condition, float $threshold): bool
    {
        return match ($condition) {
            'gt' => $val > $threshold,
            'lt' => $val < $threshold,
            'eq' => abs($val - $threshold) < 0.01,
            default => false,
        };
    }

    // ── Kirim notifikasi ────────────────────────────────────────────────────
    private function sendAlert(AlertRule $rule, $device): void
    {
        $channels  = $rule->channels ?? [];
        $message   = "[{$rule->severity}] {$rule->title} — Device: {$device->device} ({$device->ip_address}) | {$rule->conditionLabel()} | Waktu: " . now()->format('d M Y H:i:s') . ' WIB';

        if (in_array('telegram', $channels)) {
            $this->sendTelegram($rule, $device, $message);
        }

        if (in_array('email', $channels)) {
            $this->sendEmail($rule, $device, $message);
        }
    }

    private function sendTelegram(AlertRule $rule, $device, string $message): void
    {
        $cfg = AlertChannel::where('type', 'telegram')->where('is_active', true)->first();
        if (!$cfg) return;

        $token  = $cfg->config['token']   ?? '';
        $chatId = $cfg->config['chat_id'] ?? '';
        if (empty($token) || empty($chatId)) return;

        $severityEmoji = match(strtolower($rule->severity)) {
            'critical' => '🔴',
            'warning'  => '🟡',
            default    => '🔵',
        };

        $text = urlencode(
            "{$severityEmoji} *NetPulse Alert* — " . strtoupper($rule->severity) . "\n\n" .
            "📋 *Rule:* {$rule->title}\n" .
            "🖥 *Device:* `{$device->device}`\n" .
            "🌐 *IP:* `{$device->ip_address}`\n" .
            "⚡ *Kondisi:* {$rule->conditionLabel()}\n" .
            "⏰ *Waktu:* " . now()->format('d M Y H:i:s') . " WIB"
        );

        $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}&text={$text}&parse_mode=Markdown";

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_SSL_VERIFYPEER => false]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        $result = $curlErr ? ['ok' => false] : (json_decode($response, true) ?? ['ok' => false]);

        AlertHistory::create([
            'alert_rule_id' => $rule->id,
            'channel'       => 'telegram',
            'recipient'     => $chatId,
            'status'        => ($result['ok'] ?? false) ? 'sent' : 'failed',
            'message'       => $message,
            'error_message' => $curlErr ?: ($result['description'] ?? null),
            'sent_at'       => now(),
        ]);
    }

    private function sendEmail(AlertRule $rule, $device, string $message): void
    {
        $cfg = AlertChannel::where('type', 'email')->where('is_active', true)->first();
        if (!$cfg) return;

        $host     = $cfg->config['host']     ?? '';
        $port     = $cfg->config['port']     ?? 587;
        $username = $cfg->config['username'] ?? '';
        $password = $cfg->config['password'] ?? '';
        if (empty($host) || empty($username)) return;

        $fromAddress = $cfg->config['from_address'] ?? $username;
        $toAddress   = $cfg->config['to_address']   ?? $username;

        config([
            'mail.mailers.smtp.host'       => $host,
            'mail.mailers.smtp.port'       => (int) $port,
            'mail.mailers.smtp.username'   => $username,
            'mail.mailers.smtp.password'   => $password,
            'mail.mailers.smtp.encryption' => ((int)$port === 465) ? 'ssl' : 'tls',
            'mail.from.address'            => $fromAddress,
            'mail.from.name'               => 'NetPulse',
        ]);

        try {
            \Illuminate\Support\Facades\Mail::raw($message, function ($msg) use ($toAddress, $rule) {
                $msg->to($toAddress)->subject("[NetPulse] {$rule->severity}: {$rule->title}");
            });
            AlertHistory::create([
                'alert_rule_id' => $rule->id,
                'channel'       => 'email',
                'recipient'     => $toAddress,
                'status'        => 'sent',
                'message'       => $message,
                'sent_at'       => now(),
            ]);
        } catch (\Exception $e) {
            AlertHistory::create([
                'alert_rule_id' => $rule->id,
                'channel'       => 'email',
                'recipient'     => $username,
                'status'        => 'failed',
                'message'       => $message,
                'error_message' => $e->getMessage(),
                'sent_at'       => now(),
            ]);
        }
    }

    // ── Buat incident otomatis ──────────────────────────────────────────────
    private function createIncidentIfNeeded(AlertRule $rule, $device): void
    {
        $existing = \App\Models\Incident::where('device', $device->device)
            ->whereNull('resolved_at')
            ->where('issue', $rule->title)
            ->first();

        if (!$existing) {
            \App\Models\Incident::create([
                'device'     => $device->device,
                'ip_address' => $device->ip_address,
                'issue'      => $rule->title,
                'status'     => ucfirst($rule->severity),
                'started_at' => now(),
            ]);
        }
    }
}
