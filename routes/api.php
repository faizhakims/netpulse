<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\TrafficController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\UserController;

// ── Public: Authentication ────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

// ── Protected: Sanctum token required ────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth helpers ──────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });

    // ── Dashboard ─────────────────────────────────────────────────────────────
    Route::get('dashboard', [DashboardController::class, 'index']);

    // ── Devices ───────────────────────────────────────────────────────────────
    Route::apiResource('devices', DeviceController::class)->parameters(['devices' => 'name']);

    // ── Traffic ───────────────────────────────────────────────────────────────
    Route::get('traffic', [TrafficController::class, 'index']);

    // ── Alerts ────────────────────────────────────────────────────────────────
    Route::apiResource('alerts', AlertController::class);

    // ── Incidents ─────────────────────────────────────────────────────────────
    Route::apiResource('incidents', IncidentController::class)->only(['index', 'show', 'update']);

    // ── Users ─────────────────────────────────────────────────────────────────
    Route::apiResource('users', UserController::class)->only(['index', 'show']);
});
