<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Rutas públicas

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

//enviar solicitud de acceso desde la landing page
Route::post('solicitudes', [SolicitudController::class, 'store']);

//Rutas protegidas por JWT



Route::middleware('auth:api')->prefix('auth')->group(function () {

    //perfir y sesion
    Route::prefix('auth')->group(function () {  
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

//gestion de solicitudes de acceso
    Route::prefix('solicitudes')->group(function () {
        Route::get('/', [SolicitudController::class, 'index']);
        Route::post('{id_solicitud}/aprobar', [SolicitudController::class, 'aprobar']);
        Route::post('{id_solicitud}/rechazar', [SolicitudController::class, 'rechazar']);
    });

});