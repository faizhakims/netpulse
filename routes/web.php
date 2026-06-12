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
    Route::post('/login',[AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Protected pages ───────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    
    // Dashboard - All authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export-csv', [DashboardController::class, 'exportCsv'])->name('dashboard.export.csv');

    // Devices - Operator and Admin can manage, Viewer can only view
    Route::get('/device',          [DeviceController::class, 'index'])->name('device.index');
    Route::get('/device/{device}', [DeviceController::class, 'show'])->name('device.show');
    
    // Device management routes (Operator and Admin only)
    Route::middleware(['role:operator|admin'])->group(function () {
        Route::post('/device/add',    [DeviceController::class, 'addDevice'])->name('device.add');
        Route::delete('/device/{name}/delete', [DeviceController::class, 'deleteDevice'])->name('device.delete');
        Route::post('/device/ping',   [DeviceController::class, 'ping'])->name('device.ping');
        Route::post('/device/reboot', [DeviceController::class, 'reboot'])->middleware('throttle:3,1');
    });

    // Traffic monitoring - All authenticated users (Viewer has read-only access)
    Route::get('/traffic', [TrafficController::class, 'index'])->name('traffic');

    // Logs - Admin and Operator only
    Route::middleware(['role:operator|admin'])->group(function () {
        Route::get('/logs', [LogsController::class, 'index'])->name('logs');
    });

    // Alerts - Admin can manage, Operator and Viewer can only view
    Route::get('/alert', [AlertController::class, 'index'])->name('alert');
    
    // Alert management routes (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/alert/channel/save',       [AlertController::class, 'saveChannel'])->name('alert.channel.save');
        Route::post('/alert/channel/test',       [AlertController::class, 'testChannel'])->name('alert.channel.test');
        Route::post('/alert/rules',              [AlertController::class, 'storeRule'])->name('alert.rules.store');
        Route::put('/alert/rules/{id}',          [AlertController::class, 'updateRule'])->name('alert.rules.update');
        Route::post('/alert/rules/{id}/toggle',  [AlertController::class, 'toggleRule'])->name('alert.rules.toggle');
        Route::delete('/alert/rules/{id}',       [AlertController::class, 'deleteRule'])->name('alert.rules.delete');
        Route::post('/alert/rules/{id}/duplicate',[AlertController::class,'duplicateRule'])->name('alert.rules.duplicate');
    });

    // Incidents - Admin and Operator can manage, Viewer can only view
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents');

    // ── Settings ──────────────────────────────────────────────────────────────
    // Settings view - All authenticated users
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings');
    
    // Settings management - Admin only
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/settings/general',             [\App\Http\Controllers\SettingsController::class, 'saveGeneral'])->name('settings.general');
        Route::post('/settings/monitoring',          [\App\Http\Controllers\SettingsController::class, 'saveMonitoring'])->name('settings.monitoring');
        Route::post('/settings/security',            [\App\Http\Controllers\SettingsController::class, 'saveSecurity'])->name('settings.security');
        Route::post('/settings/users',               [\App\Http\Controllers\SettingsController::class, 'storeUser'])->name('settings.users.store');
        Route::put('/settings/users/{id}',           [\App\Http\Controllers\SettingsController::class, 'updateUser'])->name('settings.users.update');
        Route::post('/settings/users/{id}/toggle',   [\App\Http\Controllers\SettingsController::class, 'toggleUser'])->name('settings.users.toggle');
        Route::delete('/settings/users/{id}',        [\App\Http\Controllers\SettingsController::class, 'deleteUser'])->name('settings.users.delete');
        Route::post('/settings/clear-logs',          [\App\Http\Controllers\SettingsController::class, 'clearLogs'])->name('settings.clear-logs');
    });
    
    // Profile settings - All authenticated users
    Route::post('/settings/profile', [\App\Http\Controllers\SettingsController::class, 'saveProfile'])->name('settings.profile');
    
    // System info - Admin only
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/settings/system-info', [\App\Http\Controllers\SettingsController::class, 'systemInfo'])->name('settings.system-info');
        Route::post('/settings/backup/manual', [\App\Http\Controllers\SettingsController::class, 'triggerManualBackup'])->name('settings.backup.manual');
    });
});
