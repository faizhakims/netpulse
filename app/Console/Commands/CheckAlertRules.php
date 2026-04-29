<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AlertRule;
use App\Models\AlertHistory;
use App\Models\AlertChannel;
use Illuminate\Support\Facades\DB;

class CheckAlertRules extends Command
{
    protected $signature   = 'alerts:check {--debug : Tampilkan detail evaluasi setiap rule}';
    protected $description = 'Evaluasi semua threshold rules terhadap data device terbaru, kirim notifikasi jika terpenuhi.';

    public function handle(): void
    {
        $debug = $this->option('debug');

        $rules = AlertRule::where('is_active', true)->get();

        if ($rules->isEmpty()) {
            $this->warn('Tidak ada rule aktif.');
            return;
        }

        $this->info("Mengecek {$rules->count()} rule aktif...");

        // Ambil status terbaru per device (MariaDB compatible: MAX(id) per device)
        $latestDevices = DB::table('device_status')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('device_status')->groupBy('device');
            })
            ->get()
            ->keyBy('device');

        if ($debug) {
            $this->line("  📡 Device ditemukan: " . $latestDevices->count());
            foreach ($latestDevices as $d) {
                $this->line("     - {$d->device} [{$d->ip_address}] status={$d->status} latency={$d->latency_ms}ms");
            }
        }

        // Ambil rata-rata latency per device
        $avgLatency = DB::table('device_status')
            ->select('device', DB::raw('AVG(latency_ms) as avg_latency'))
            ->groupBy('device')
            ->get()
            ->keyBy('device');

        foreach ($rules as $rule) {
            $targets = $this->resolveTargets($rule, $latestDevices);

            if ($debug) {
                $this->line("\n🔍 Rule: [{$rule->title}] metric={$rule->metric_type} condition={$rule->condition} threshold={$rule->threshold_value} duration={$rule->duration}");
                $this->line("   Targets: " . collect($targets)->pluck('device')->join(', '));
            }

            foreach ($targets as $device) {
                // Cooldown per rule+device — cegah spam tapi tetap cek device lain
                $cooldownKey = "rule_{$rule->id}_device_{$device->device}";
                if ($this->isInCooldown($rule, $device->device)) {
                    if ($debug) $this->line("  ⏳ [{$device->device}] masih cooldown, skip.");
                    continue;
                }

                $triggered = $this->evaluate($rule, $device, $avgLatency, $debug);

                if ($triggered) {
                    $this->line("  ⚡ TRIGGER: [{$rule->title}] → [{$device->device}]");
                    $this->sendAlert($rule, $device);

                    $rule->increment('trigger_count');
                    $rule->last_triggered_at = now();
                    $rule->saveQuietly();

                    $this->createIncidentIfNeeded($rule, $device);
                } else {
                    if ($debug) $this->line("  ✅ [{$device->device}] kondisi tidak terpenuhi, tidak trigger.");
                }
            }
        }

        $this->info('Alert check selesai — ' . now()->format('H:i:s') . ' WIB');
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

    // ── Evaluasi kondisi ────────────────────────────────────────────────────
    private function evaluate(AlertRule $rule, $device, $avgLatency, bool $debug = false): bool
    {
        $metric    = $rule->metric_type ?? 'latency';
        $condition = $rule->condition   ?? 'gt';
        $threshold = (float) ($rule->threshold_value ?? 0);
        $minutes   = $this->durationToMinutes($rule->duration ?? '1m');

        switch ($metric) {

            case 'status':
                if ($condition === 'is_down') {
                    // Ambil semua record dalam window [duration] menit terakhir
                    $window = DB::table('device_status')
                        ->where('device', $device->device)
                        ->where('checked_at', '>=', now()->subMinutes($minutes))
                        ->orderByDesc('checked_at')
                        ->get();

                    // Jika tidak ada data dalam window, cek apakah record terakhir = down
                    if ($window->isEmpty()) {
                        $last = DB::table('device_status')
                            ->where('device', $device->device)
                            ->orderByDesc('checked_at')
                            ->first();
                        $result = $last && strtolower($last->status) === 'down';
                        if ($debug) $this->line("  → status(no window data): last={$last?->status} → " . ($result ? 'TRIGGER' : 'skip'));
                        return $result;
                    }

                    // Trigger jika SEMUA record dalam window = down (tidak ada 'up' sama sekali)
                    $hasUp = $window->contains(fn($r) => strtolower($r->status) === 'up');
                    $result = !$hasUp;
                    if ($debug) $this->line("  → status(window {$minutes}m): {$window->count()} records, hasUp=" . ($hasUp?'yes':'no') . " → " . ($result?'TRIGGER':'skip'));
                    return $result;
                }

                if ($condition === 'is_up') {
                    return strtolower($device->status) === 'up';
                }
                return false;

            case 'latency':
                if (strtolower($device->status) === 'down') {
                    if ($debug) $this->line("  → latency: device DOWN, skip");
                    return false;
                }
                $latencyRow = $avgLatency->get($device->device);
                $val = ($latencyRow && $latencyRow->avg_latency !== null)
                    ? (float) $latencyRow->avg_latency
                    : (($device->latency_ms !== null) ? (float) $device->latency_ms : null);
                if ($val === null) {
                    if ($debug) $this->line("  → latency: NULL, skip");
                    return false;
                }
                $result = $this->compare($val, $condition, $threshold);
                if ($debug) $this->line("  → latency: val={$val}ms {$condition} {$threshold} → " . ($result?'TRIGGER':'skip'));
                return $result;

            case 'packet_loss':
                $recent = DB::table('device_status')
                    ->where('device', $device->device)
                    ->orderByDesc('id')->limit(10)->get();
                if ($recent->isEmpty()) return false;
                $lossRate = ($recent->where('status', 'down')->count() / $recent->count()) * 100;
                $result = $this->compare($lossRate, $condition, $threshold);
                if ($debug) $this->line("  → packet_loss: {$lossRate}% {$condition} {$threshold} → " . ($result?'TRIGGER':'skip'));
                return $result;

            case 'bandwidth':
                $rounds = DB::table('interface_traffic')
                    ->where('device', $device->device)
                    ->select('collected_at', DB::raw('SUM(bytes_in + bytes_out) as total_bytes'))
                    ->groupBy('collected_at')
                    ->orderByDesc('collected_at')
                    ->limit(2)
                    ->get();
                if ($rounds->count() < 2) return false;
                $newer    = $rounds->first();
                $older    = $rounds->last();
                $timeDiff = strtotime($newer->collected_at) - strtotime($older->collected_at);
                if ($timeDiff <= 0) return false;
                $bytesDelta = $newer->total_bytes - $older->total_bytes;
                if ($bytesDelta < 0) return false;
                $mbps = ($bytesDelta * 8) / $timeDiff / 1_000_000;
                $result = $this->compare($mbps, $condition, $threshold);
                if ($debug) $this->line("  → bandwidth: {$mbps}Mbps {$condition} {$threshold} → " . ($result?'TRIGGER':'skip'));
                return $result;
        }

        return false;
    }

    private function compare(float $val, string $condition, float $threshold): bool
    {
        return match ($condition) {
            'gt'  => $val > $threshold,
            'lt'  => $val < $threshold,
            'eq'  => abs($val - $threshold) < 0.01,
            default => false,
        };
    }

    // ── Kirim notifikasi ────────────────────────────────────────────────────
    private function sendAlert(AlertRule $rule, $device): void
    {
        $channels = $rule->channels ?? [];
        $message  = "[{$rule->severity}] {$rule->title} — Device: {$device->device} ({$device->ip_address}) | {$rule->conditionLabel()} | " . now()->format('d M Y H:i:s') . ' WIB';

        if (in_array('telegram', $channels)) $this->sendTelegram($rule, $device, $message);
        if (in_array('email',    $channels)) $this->sendEmail($rule, $device, $message);
    }

    private function sendTelegram(AlertRule $rule, $device, string $message): void
    {
        $cfg = AlertChannel::where('type', 'telegram')->where('is_active', true)->first();
        if (!$cfg) { $this->warn('  Telegram channel tidak aktif/tidak ada.'); return; }

        $token  = $cfg->config['token']   ?? '';
        $chatId = $cfg->config['chat_id'] ?? '';
        if (empty($token) || empty($chatId)) { $this->warn('  Telegram token/chat_id kosong.'); return; }

        $emoji = match(strtolower($rule->severity)) { 'critical' => '🔴', 'warning' => '🟡', default => '🔵' };

        $text = urlencode(
            "{$emoji} *NetPulse Alert* — " . strtoupper($rule->severity) . "\n\n" .
            "📋 *Rule:* {$rule->title}\n" .
            "🖥 *Device:* `{$device->device}`\n" .
            "🌐 *IP:* `{$device->ip_address}`\n" .
            "⚡ *Kondisi:* {$rule->conditionLabel()}\n" .
            "⏰ *Waktu:* " . now()->format('d M Y H:i:s') . " WIB"
        );

        $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}&text={$text}&parse_mode=Markdown";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_SSL_VERIFYPEER => false]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        $result = $curlErr ? ['ok' => false] : (json_decode($response, true) ?? ['ok' => false]);
        $ok     = $result['ok'] ?? false;

        $this->line("  📨 Telegram → " . ($ok ? '✅ terkirim' : '❌ gagal: ' . ($curlErr ?: ($result['description'] ?? 'unknown'))));

        AlertHistory::create([
            'alert_rule_id' => $rule->id,
            'channel'       => 'telegram',
            'recipient'     => $chatId,
            'status'        => $ok ? 'sent' : 'failed',
            'message'       => $message,
            'error_message' => $curlErr ?: ($result['description'] ?? null),
            'sent_at'       => now(),
        ]);
    }

    private function sendEmail(AlertRule $rule, $device, string $message): void
    {
        $cfg = AlertChannel::where('type', 'email')->where('is_active', true)->first();
        if (!$cfg) { $this->warn('  Email channel tidak aktif/tidak ada.'); return; }

        $host        = $cfg->config['host']         ?? '';
        $port        = $cfg->config['port']         ?? 587;
        $username    = $cfg->config['username']     ?? '';
        $password    = $cfg->config['password']     ?? '';
        $fromAddress = $cfg->config['from_address'] ?? $username;
        $toAddress   = $cfg->config['to_address']   ?? $username;

        if (empty($host) || empty($username)) { $this->warn('  Email config belum lengkap.'); return; }

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
                $msg->to($toAddress)->subject("[NetPulse] " . strtoupper($rule->severity) . ": {$rule->title}");
            });
            $this->line("  📧 Email → ✅ terkirim ke {$toAddress}");
            AlertHistory::create([
                'alert_rule_id' => $rule->id, 'channel' => 'email', 'recipient' => $toAddress,
                'status' => 'sent', 'message' => $message, 'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            $this->warn("  📧 Email → ❌ gagal: " . $e->getMessage());
            AlertHistory::create([
                'alert_rule_id' => $rule->id, 'channel' => 'email', 'recipient' => $toAddress,
                'status' => 'failed', 'message' => $message, 'error_message' => $e->getMessage(), 'sent_at' => now(),
            ]);
        }
    }

    // ── Cooldown per rule+device (bukan per rule saja) ─────────────────────
    private function isInCooldown(AlertRule $rule, string $deviceName): bool
    {
        $minutes = max($this->durationToMinutes($rule->duration ?? '1m'), 5); // minimum 5 menit cooldown

        return AlertHistory::where('alert_rule_id', $rule->id)
            ->where('message', 'like', "%Device: {$deviceName}%")
            ->where('status', 'sent')
            ->where('sent_at', '>=', now()->subMinutes($minutes))
            ->exists();
    }

    private function durationToMinutes(string $duration): int
    {
        return (int) str_replace('m', '', $duration);
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
            $this->line("  🚨 Incident baru dibuat untuk [{$device->device}]");
        }
    }
}