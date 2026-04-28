<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\TrafficController;
use App\Http\Controllers\LogsController;

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/',      fn() => view('login'))->name('home');
Route::get('/login', fn() => view('login'))->name('login');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// ── Main Pages ────────────────────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/device',          [DeviceController::class, 'index'])->name('device.index');
Route::get('/device/{device}', [DeviceController::class, 'show'])->name('device.show');

Route::get('/traffic',   [TrafficController::class, 'index'])->name('traffic');
Route::get('/logs',      [LogsController::class,    'index'])->name('logs');

// ── Halaman belum selesai (masih static) ─────────────────────────────────────
Route::get('/alert',     fn() => view('alert'))->name('alert');
Route::get('/incidents', fn() => view('incidents'))->name('incidents');
Route::get('/details',   fn() => view('details'))->name('details');
