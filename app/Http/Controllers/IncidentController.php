<?php

namespace App\Http\Controllers;

use App\Models\Incident;

class IncidentController extends Controller
{
    public function index()
    {
        // ── Active incidents ──────────────────────────────────────────────────
        $activeIncidents = Incident::active()
            ->orderByRaw("FIELD(status,'Critical','Warning','Monitoring','Info')")
            ->orderBy('started_at', 'desc')
            ->get();

        // ── Stats ──────────────────────────────────────────────────────────────
        $deviceDown     = Incident::active()->where('status', 'Critical')->count();
        $latencySpikes  = Incident::active()->where('issue', 'like', '%latency%')->count();
        $unresolvedCount = Incident::active()->count();

        $total   = Incident::count();
        $resolved = Incident::resolved()->count();
        $successRate = $total > 0
            ? number_format(($resolved / $total) * 100, 1)
            : '100.0';

        // ── Resolved log sidebar (5 terbaru) ──────────────────────────────────
        $resolvedLog = Incident::resolved()
            ->orderBy('resolved_at', 'desc')
            ->limit(5)
            ->get();

        // ── Full history panel (50 terakhir) ──────────────────────────────────
        $fullHistory = Incident::resolved()
            ->orderBy('resolved_at', 'desc')
            ->limit(50)
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
