<?php

namespace App\Http\Controllers;

use App\Models\Incident;

class IncidentController extends Controller
{
    public function index()
    {
        // ── Active incidents ──────────────────────────────────────────────────
        // Urutan: Critical → Warning → Monitoring → Info, lalu terbaru dulu
        $activeIncidents = Incident::active()
            ->orderByRaw("FIELD(status,'Critical','Warning','Monitoring','Info')")
            ->orderBy('started_at', 'desc')
            ->get();

        // ── Stats ──────────────────────────────────────────────────────────────
        $deviceDown = Incident::active()
            ->where('status', 'Critical')
            ->count();

        // Latency spikes: incident active dengan issue mengandung kata 'latency'
        $latencySpikes = Incident::active()
            ->where(function ($q) {
                $q->where('issue', 'like', '%latency%')
                  ->orWhere('issue', 'like', '%Latency%');
            })
            ->count();

        $unresolvedCount = Incident::active()->count();

        // Success rate: persentase incident yang sudah resolved dari total
        $total    = Incident::count();
        $resolved = Incident::resolved()->count();
        $successRate = $total > 0
            ? number_format(($resolved / $total) * 100, 1)
            : '100.0';

        // ── Resolved log sidebar (5 terbaru) ──────────────────────────────────
        $resolvedLog = Incident::resolved()
            ->orderBy('resolved_at', 'desc')
            ->limit(5)
            ->get();

        // ── Full history panel (semua, terbaru dulu) ──────────────────────────
        $fullHistory = Incident::resolved()
            ->orderBy('resolved_at', 'desc')
            ->limit(200)
            ->get();

        return view('incidents', compact(
            'activeIncidents',
            'deviceDown',
            'latencySpikes',
            'unresolvedCount',
            'successRate',
            'resolvedLog',
            'fullHistory',
        ));
    }
}