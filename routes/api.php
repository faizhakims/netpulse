<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\TrafficController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\UserController;


Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });
    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::apiResource('devices', DeviceController::class)->parameters(['devices' => 'name']);

    Route::get('traffic', [TrafficController::class, 'index']);

    Route::apiResource('alerts', AlertController::class);

    Route::apiResource('incidents', IncidentController::class)->only(['index', 'show', 'update']);

    Route::apiResource('users', UserController::class)->only(['index', 'show']);
});
