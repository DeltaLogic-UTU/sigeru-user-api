<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SolicitudController;
use Illuminate\Support\Facades\Route;

//Rutas públicas

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

//enviar solicitud de acceso desde la landing page
Route::post('solicitudes', [SolicitudController::class, 'store']);

//Rutas protegidas por JWT



Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Gestión de solicitudes de acceso
Route::middleware('auth:api')->prefix('solicitudes')->group(function () {
    Route::get('/', [SolicitudController::class, 'index']);
    Route::post('{id_solicitud}/aprobar', [SolicitudController::class, 'aprobar']);
    Route::post('{id_solicitud}/rechazar', [SolicitudController::class, 'rechazar']);
});