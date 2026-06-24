<?php

namespace App\Services;

use App\Models\AlertChannel;
use App\Models\AlertHistory;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function testChannel(string $type, array $inlineConfig = [])
    {
        $channel = AlertChannel::where('type', $type)->first();
        $config  = array_merge($channel?->config ?? [], $inlineConfig);

        if ($type === 'telegram') {
            return $this->testTelegram($config);
        }

        if ($type === 'email') {
            return $this->testEmail($config);
        }

        throw new \Exception('Channel tidak dikenal.');
    }

    private function testTelegram(array $config)
    {
        $token  = $config['token']   ?? '';
        $chatId = $config['chat_id'] ?? '';

        if (empty($token) || empty($chatId)) {
            throw new \Exception('Bot Token dan Chat ID harus diisi terlebih dahulu.');
        }

        if (!preg_match('/^\d+:[A-Za-z0-9_-]{35,}$/', $token)) {
            throw new \Exception('Format Bot Token Telegram tidak valid.');
        }

        $text = "✅ *NetPulse Test Alert*\n\nKoneksi Telegram berhasil dikonfigurasi.\nWaktu: " . now()->format('d M Y H:i:s') . ' WIB';

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'    => $chatId,
                    'text'       => $text,
                    'parse_mode' => 'Markdown',
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->logAlertHistory('telegram', $chatId, 'failed', "Connection error: {$e->getMessage()}");
            throw new \Exception("Koneksi ke Telegram API gagal: {$e->getMessage()}");
        }

        $result = $response->json();

        if ($result['ok'] ?? false) {
            $this->logAlertHistory('telegram', $chatId, 'sent');
            return 'Pesan test berhasil dikirim ke Telegram! Cek chat Anda.';
        }

        $errDesc = $result['description'] ?? 'Unknown Telegram error';
        $this->logAlertHistory('telegram', $chatId, 'failed', $errDesc);
        throw new \Exception("Telegram error: {$errDesc}");
    }

    private function testEmail(array $config)
    {
        $host     = $config['host']     ?? '';
        $port     = $config['port']     ?? 587;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        if (empty($host) || empty($username)) {
            throw new \Exception('SMTP host dan username harus diisi.');
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
            Mail::raw(
                "✅ NetPulse Test Alert\n\nKoneksi email berhasil dikonfigurasi.\nWaktu: " . now()->format('d M Y H:i:s') . ' WIB',
                function ($msg) use ($toAddress) {
                    $msg->to($toAddress)->subject('NetPulse – Test Alert');
                }
            );
            $this->logAlertHistory('email', $toAddress, 'sent');
            return 'Email test berhasil dikirim ke ' . $toAddress . '! Cek inbox Anda.';
        } catch (\Exception $e) {
            $this->logAlertHistory('email', $toAddress, 'failed', $e->getMessage());
            throw new \Exception('SMTP error: ' . $e->getMessage());
        }
    }

    private function logAlertHistory(string $channel, string $recipient, string $status, ?string $errorMessage = null)
    {
        AlertHistory::create([
            'channel'       => $channel,
            'recipient'     => $recipient,
            'status'        => $status,
            'message'       => $status === 'sent' ? '[Test] NetPulse connection test — ' . now()->format('d M Y H:i:s') : '[Test] NetPulse connection test',
            'error_message' => $errorMessage,
            'sent_at'       => now(),
        ]);
    }
}
