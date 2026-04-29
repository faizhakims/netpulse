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
    Route::get('/dashboard/export-csv', [DashboardController::class, 'exportCsv'])->name('dashboard.export.csv');

    Route::get('/device',          [DeviceController::class, 'index'])->name('device.index');
    Route::get('/device/{device}', [DeviceController::class, 'show'])->name('device.show');

    Route::get('/traffic', [TrafficController::class, 'index'])->name('traffic');
    Route::get('/logs',    [LogsController::class,    'index'])->name('logs');

    Route::get('/alert',                     [AlertController::class, 'index'])->name('alert');
    Route::post('/alert/channel/save',       [AlertController::class, 'saveChannel'])->name('alert.channel.save');
    Route::post('/alert/channel/test',       [AlertController::class, 'testChannel'])->name('alert.channel.test');
    Route::post('/alert/rules',              [AlertController::class, 'storeRule'])->name('alert.rules.store');
    Route::put('/alert/rules/{id}',          [AlertController::class, 'updateRule'])->name('alert.rules.update');
    Route::post('/alert/rules/{id}/toggle',  [AlertController::class, 'toggleRule'])->name('alert.rules.toggle');
    Route::delete('/alert/rules/{id}',       [AlertController::class, 'deleteRule'])->name('alert.rules.delete');
    Route::post('/alert/rules/{id}/duplicate',[AlertController::class,'duplicateRule'])->name('alert.rules.duplicate');
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents');

    // ── Settings ──────────────────────────────────────────────────────────────
    Route::get('/settings',                      [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/general',             [\App\Http\Controllers\SettingsController::class, 'saveGeneral'])->name('settings.general');
    Route::post('/settings/monitoring',          [\App\Http\Controllers\SettingsController::class, 'saveMonitoring'])->name('settings.monitoring');
    Route::post('/settings/security',            [\App\Http\Controllers\SettingsController::class, 'saveSecurity'])->name('settings.security');
    Route::post('/settings/profile',             [\App\Http\Controllers\SettingsController::class, 'saveProfile'])->name('settings.profile');
    Route::post('/settings/users',               [\App\Http\Controllers\SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::put('/settings/users/{id}',           [\App\Http\Controllers\SettingsController::class, 'updateUser'])->name('settings.users.update');
    Route::post('/settings/users/{id}/toggle',   [\App\Http\Controllers\SettingsController::class, 'toggleUser'])->name('settings.users.toggle');
    Route::delete('/settings/users/{id}',        [\App\Http\Controllers\SettingsController::class, 'deleteUser'])->name('settings.users.delete');
    Route::post('/settings/clear-logs',          [\App\Http\Controllers\SettingsController::class, 'clearLogs'])->name('settings.clear-logs');
    Route::get('/settings/system-info',          [\App\Http\Controllers\SettingsController::class, 'systemInfo'])->name('settings.system-info');
});