<?php

namespace App\Http\Controllers;

use App\Models\AlertRule;
use App\Models\AlertHistory;
use App\Models\AlertChannel;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        $telegram = AlertChannel::where('type', 'telegram')->first();
        $emailCfg = AlertChannel::where('type', 'email')->first();
        $thresholdRules = AlertRule::orderBy('sort_order')->orderByRaw("FIELD(severity,'critical','warning','info')")->get();
        $activeRules  = AlertRule::where('is_active', true)->count();
        $sentLast24h  = AlertHistory::where('sent_at', '>=', now()->subDay())->count();
        $failedAlerts = AlertHistory::where('status', 'failed')->where('sent_at', '>=', now()->subDay())->count();
        $sentCount    = AlertHistory::where('status', 'sent')->where('sent_at', '>=', now()->subDay())->count();
        $successRate  = $sentLast24h > 0 ? number_format(($sentCount / $sentLast24h) * 100, 1) : '100.0';
        $alertHistory = AlertHistory::with('rule')->orderBy('sent_at', 'desc')->limit(10)->get();
        $allHistory   = AlertHistory::with('rule')->orderBy('sent_at', 'desc')->get();
        return view('alert', compact('telegram','emailCfg','thresholdRules','activeRules','sentLast24h','failedAlerts','successRate','alertHistory','allHistory'));
    }

    public function saveChannel(Request $request)
    {
        $type    = $request->input('type');
        $channel = AlertChannel::firstOrNew(['type' => $type]);
        $channel->is_active = $request->boolean('is_active');
        $channel->config    = $request->input('config', []);
        $channel->save();
        return response()->json(['ok' => true, 'message' => ucfirst($type) . ' settings saved.']);
    }

    public function testChannel(Request $request)
    {
        $type = $request->input('type');

        // Ambil token/config dari request (user mungkin belum save ke DB)
        $inlineConfig = $request->input('config', []);

        // Fallback ke DB jika tidak ada di request
        $channel = AlertChannel::where('type', $type)->first();
        $config  = array_merge($channel?->config ?? [], $inlineConfig);

        if ($type === 'telegram') {
            $token  = $config['token']   ?? '';
            $chatId = $config['chat_id'] ?? '';

            if (empty($token) || empty($chatId)) {
                return response()->json(['ok' => false, 'message' => 'Bot Token dan Chat ID harus diisi terlebih dahulu.']);
            }

            $text = urlencode("✅ *NetPulse Test Alert*\n\nKoneksi Telegram berhasil dikonfigurasi.\nWaktu: " . now()->format('d M Y H:i:s') . ' WIB');
            $url  = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}&text={$text}&parse_mode=Markdown";

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $response = curl_exec($ch);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                AlertHistory::create([
                    'channel'       => 'telegram',
                    'recipient'     => $chatId,
                    'status'        => 'failed',
                    'message'       => '[Test] NetPulse connection test',
                    'error_message' => "cURL error: {$curlErr}",
                    'sent_at'       => now(),
                ]);
                return response()->json(['ok' => false, 'message' => "cURL error: {$curlErr}"]);
            }

            $result = json_decode($response, true);
            if ($result['ok'] ?? false) {
                AlertHistory::create([
                    'channel'   => 'telegram',
                    'recipient' => $chatId,
                    'status'    => 'sent',
                    'message'   => '[Test] NetPulse connection test — ' . now()->format('d M Y H:i:s'),
                    'sent_at'   => now(),
                ]);
                return response()->json(['ok' => true, 'message' => 'Pesan test berhasil dikirim ke Telegram! Cek chat Anda.']);
            }

            $errDesc = $result['description'] ?? 'Unknown Telegram error';
            AlertHistory::create([
                'channel'       => 'telegram',
                'recipient'     => $chatId,
                'status'        => 'failed',
                'message'       => '[Test] NetPulse connection test',
                'error_message' => $errDesc,
                'sent_at'       => now(),
            ]);
            return response()->json(['ok' => false, 'message' => "Telegram error: {$errDesc}"]);
        }

        if ($type === 'email') {
            $host     = $config['host']     ?? '';
            $port     = $config['port']     ?? 587;
            $username = $config['username'] ?? '';
            $password = $config['password'] ?? '';

            if (empty($host) || empty($username)) {
                return response()->json(['ok' => false, 'message' => 'SMTP host dan username harus diisi.']);
            }

            $fromAddress = $config['from_address'] ?? $username;
            $toAddress   = $config['to_address']   ?? $username;

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
                \Illuminate\Support\Facades\Mail::raw(
                    "✅ NetPulse Test Alert\n\nKoneksi email berhasil dikonfigurasi.\nWaktu: " . now()->format('d M Y H:i:s') . ' WIB',
                    function ($msg) use ($toAddress) {
                        $msg->to($toAddress)->subject('NetPulse – Test Alert');
                    }
                );
                AlertHistory::create([
                    'channel'   => 'email',
                    'recipient' => $toAddress,
                    'status'    => 'sent',
                    'message'   => '[Test] NetPulse connection test — ' . now()->format('d M Y H:i:s'),
                    'sent_at'   => now(),
                ]);
                return response()->json(['ok' => true, 'message' => 'Email test berhasil dikirim ke ' . $toAddress . '! Cek inbox Anda.']);
            } catch (\Exception $e) {
                AlertHistory::create([
                    'channel'       => 'email',
                    'recipient'     => $toAddress,
                    'status'        => 'failed',
                    'message'       => '[Test] NetPulse connection test',
                    'error_message' => $e->getMessage(),
                    'sent_at'       => now(),
                ]);
                return response()->json(['ok' => false, 'message' => 'SMTP error: ' . $e->getMessage()]);
            }
        }

        return response()->json(['ok' => false, 'message' => 'Channel tidak dikenal.']);
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:120',
            'target_device'   => 'nullable|string|max:100',
            'metric_type'     => 'required|in:latency,status,cpu,memory,bandwidth,packet_loss',
            'condition'       => 'required|in:gt,lt,eq,is_down,is_up',
            'threshold_value' => 'nullable|numeric',
            'duration'        => 'required|in:1m,5m,10m,15m,30m',
            'severity'        => 'required|in:critical,warning,info',
            'channels'        => 'required|array|min:1',
            'channels.*'      => 'in:telegram,email',
            'is_active'       => 'boolean',
        ]);
        $data['description'] = $data['title'];
        $data['sort_order']  = AlertRule::max('sort_order') + 1;
        $data['is_active']   = $request->boolean('is_active', true);
        $rule = AlertRule::create($data);
        return response()->json(['ok' => true, 'message' => 'Rule created successfully.', 'rule' => $rule]);
    }

    public function updateRule(Request $request, $id)
    {
        $rule = AlertRule::findOrFail($id);
        $data = $request->validate([
            'title'           => 'required|string|max:120',
            'target_device'   => 'nullable|string|max:100',
            'metric_type'     => 'required|in:latency,status,cpu,memory,bandwidth,packet_loss',
            'condition'       => 'required|in:gt,lt,eq,is_down,is_up',
            'threshold_value' => 'nullable|numeric',
            'duration'        => 'required|in:1m,5m,10m,15m,30m',
            'severity'        => 'required|in:critical,warning,info',
            'channels'        => 'required|array|min:1',
            'channels.*'      => 'in:telegram,email',
            'is_active'       => 'boolean',
        ]);
        $data['description'] = $data['title'];
        $data['is_active']   = $request->boolean('is_active', $rule->is_active);
        $rule->update($data);
        return response()->json(['ok' => true, 'message' => 'Rule updated successfully.', 'rule' => $rule->fresh()]);
    }

    public function toggleRule($id)
    {
        $rule = AlertRule::findOrFail($id);
        $rule->is_active = !$rule->is_active;
        $rule->save();
        return response()->json(['ok' => true, 'is_active' => $rule->is_active]);
    }

    public function deleteRule($id)
    {
        AlertRule::findOrFail($id)->delete();
        return response()->json(['ok' => true, 'message' => 'Rule deleted.']);
    }

    public function duplicateRule($id)
    {
        $original = AlertRule::findOrFail($id);
        $copy = $original->replicate();
        $copy->title      = $original->title . ' (Copy)';
        $copy->sort_order = AlertRule::max('sort_order') + 1;
        $copy->save();
        return response()->json(['ok' => true, 'message' => 'Rule duplicated.', 'rule' => $copy]);
    }
}
