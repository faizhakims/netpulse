<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\TrafficController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\SettingsController;

// ── Auth (guest only) ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/',      [AuthController::class, 'showLogin'])->name('home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',[AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Protected pages ───────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // ── Dashboard & Traffic & Logs (all roles) ────────────────────────────────
    Route::middleware('permission:view dashboard')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/export-csv', [DashboardController::class, 'exportCsv'])->name('dashboard.export.csv');
    });

    Route::get('/traffic', [TrafficController::class, 'index'])->name('traffic')->middleware('permission:view traffic');
    Route::get('/logs',    [LogsController::class,    'index'])->name('logs')->middleware('permission:view logs');

    // ── Devices — view (all roles) ────────────────────────────────────────────
    Route::get('/device',          [DeviceController::class, 'index'])->name('device.index')->middleware('permission:view devices');
    Route::get('/device/{device}', [DeviceController::class, 'show'])->name('device.show')->middleware('permission:view devices');

    // ── Devices — manage (admin + operator) ──────────────────────────────────
    Route::middleware('permission:manage devices')->group(function () {
        Route::post('/device/ping',                    [DeviceController::class, 'ping'])->name('device.ping');
        Route::post('/device/reboot',                  [DeviceController::class, 'reboot'])->middleware('throttle:3,1');
        Route::post('/device/add',                     [DeviceController::class, 'addDevice'])->name('device.add');
        Route::delete('/device/{name}/delete',         [DeviceController::class, 'deleteDevice'])->name('device.delete');
    });

    // ── Alerts — view (all roles) ─────────────────────────────────────────────
    Route::get('/alert', [AlertController::class, 'index'])->name('alert')->middleware('permission:view alerts');

    // ── Alerts — manage (admin only) ──────────────────────────────────────────
    Route::middleware('permission:manage alerts')->group(function () {
        Route::post('/alert/channel/save',        [AlertController::class, 'saveChannel'])->name('alert.channel.save');
        Route::post('/alert/channel/test',        [AlertController::class, 'testChannel'])->name('alert.channel.test');
        Route::post('/alert/rules',               [AlertController::class, 'storeRule'])->name('alert.rules.store');
        Route::put('/alert/rules/{id}',           [AlertController::class, 'updateRule'])->name('alert.rules.update');
        Route::post('/alert/rules/{id}/toggle',   [AlertController::class, 'toggleRule'])->name('alert.rules.toggle');
        Route::delete('/alert/rules/{id}',        [AlertController::class, 'deleteRule'])->name('alert.rules.delete');
        Route::post('/alert/rules/{id}/duplicate',[AlertController::class, 'duplicateRule'])->name('alert.rules.duplicate');
    });

    // ── Incidents — view (all roles) ──────────────────────────────────────────
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents')->middleware('permission:view incidents');

    // ── Settings — admin only ─────────────────────────────────────────────────
    Route::middleware('permission:manage settings')->group(function () {
        Route::get('/settings',                     [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/general',            [SettingsController::class, 'saveGeneral'])->name('settings.general');
        Route::post('/settings/monitoring',         [SettingsController::class, 'saveMonitoring'])->name('settings.monitoring');
        Route::post('/settings/security',           [SettingsController::class, 'saveSecurity'])->name('settings.security');
        Route::get('/settings/system-info',         [SettingsController::class, 'systemInfo'])->name('settings.system-info');
        Route::post('/settings/clear-logs',         [SettingsController::class, 'clearLogs'])->name('settings.clear-logs');
        Route::post('/settings/backup/manual',      [SettingsController::class, 'triggerManualBackup'])->name('settings.backup.manual');
    });

    // ── Profile — any authenticated user can update their own profile ─────────
    Route::post('/settings/profile', [SettingsController::class, 'saveProfile'])->name('settings.profile');

    // ── User management — admin only ──────────────────────────────────────────
    Route::middleware('permission:manage users')->group(function () {
        Route::post('/settings/users',             [SettingsController::class, 'storeUser'])->name('settings.users.store');
        Route::put('/settings/users/{id}',         [SettingsController::class, 'updateUser'])->name('settings.users.update');
        Route::post('/settings/users/{id}/toggle', [SettingsController::class, 'toggleUser'])->name('settings.users.toggle');
        Route::delete('/settings/users/{id}',      [SettingsController::class, 'deleteUser'])->name('settings.users.delete');
    });
});
