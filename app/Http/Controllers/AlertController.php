<?php

namespace App\Http\Controllers;

use App\Models\AlertRule;
use App\Models\AlertHistory;
use App\Models\AlertChannel;

class AlertController extends Controller
{
    public function index()
    {
        // ── Channel configs ────────────────────────────────────────────────────
        $telegram = AlertChannel::where('type', 'telegram')->first();
        $emailCfg = AlertChannel::where('type', 'email')->first();

        // ── Threshold rules ────────────────────────────────────────────────────
        $thresholdRules = AlertRule::orderByRaw("FIELD(severity,'critical','warning','info')")
            ->get();

        // ── Stats ──────────────────────────────────────────────────────────────
        $activeRules  = AlertRule::where('is_active', true)->count();
        $sentLast24h  = AlertHistory::where('sent_at', '>=', now()->subDay())->count();
        $failedAlerts = AlertHistory::where('status', 'failed')
            ->where('sent_at', '>=', now()->subDay())
            ->count();
        $sentCount    = AlertHistory::where('status', 'sent')
            ->where('sent_at', '>=', now()->subDay())
            ->count();
        $successRate  = $sentLast24h > 0
            ? number_format(($sentCount / $sentLast24h) * 100, 1)
            : '100.0';

        // ── Alert history (10 terbaru untuk tabel) ──────────────────────────
        $alertHistory = AlertHistory::with('rule')
            ->orderBy('sent_at', 'desc')
            ->limit(10)
            ->get();

        return view('alert', compact(
            'telegram',
            'emailCfg',
            'thresholdRules',
            'activeRules',
            'sentLast24h',
            'failedAlerts',
            'successRate',
            'alertHistory',
        ));
    }
}
