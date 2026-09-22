<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordRecoveryController;
use App\Http\Controllers\Api\SolicitudAccesoController;
use Illuminate\Support\Facades\Route;

// Rutas públicas

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Rutas protegidas por JWT

Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Rutas para solicitudes de acceso
Route::prefix('solicitudes')->group(function () {
    Route::post('/', [SolicitudAccesoController::class, 'store']); // Landing page
    Route::get('/', [SolicitudAccesoController::class, 'index'])->middleware('auth:api'); // Admin
    Route::put('/{id}/resolver', [SolicitudAccesoController::class, 'resolver'])->middleware('auth:api'); // Admin
});

// Rutas para recuperación de contraseña
Route::prefix('auth')->group(function () {
    Route::post('/forgot-password', [PasswordRecoveryController::class, 'forgot']);
    Route::post('/reset-password', [PasswordRecoveryController::class, 'reset']);
});
