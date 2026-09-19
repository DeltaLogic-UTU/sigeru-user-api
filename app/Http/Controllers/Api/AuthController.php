<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    /**
     * Iniciar sesión de usuario y generar un token JWT. 
     */

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'cedula' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $token = auth('api')->attempt($credentials);

        if (! $token) {
            return response()->json([
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

/**
 * Obtener los datos del usuario autenticado
 */

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => auth('api')->user()->load('usuario')
        ]);
    }

    //Cerrar sesión (invalidar el token)

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    //renovar el token expirado

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    //Estructura de respuesta con token JWT


    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'message' => 'Autenticación existosa',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
        ]);
    }
}
