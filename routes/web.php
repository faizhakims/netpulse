<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\TrafficController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\AlertController;

// ── Auth (guest only) ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/',      [AuthController::class, 'showLogin'])->name('home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',[AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Protected pages ───────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/device',          [DeviceController::class, 'index'])->name('device.index');
    Route::get('/device/{device}', [DeviceController::class, 'show'])->name('device.show');

    Route::get('/traffic', [TrafficController::class, 'index'])->name('traffic');
    Route::get('/logs',    [LogsController::class,    'index'])->name('logs');

    Route::get('/alert',     [AlertController::class,    'index'])->name('alert');
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents');
});
