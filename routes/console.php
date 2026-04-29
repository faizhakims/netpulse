<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── NetPulse Alert Engine ──────────────────────────────────────────────────
// Cek semua threshold rules setiap 1 menit
Schedule::command('alerts:check')->everyMinute();
