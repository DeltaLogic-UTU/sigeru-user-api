<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PasswordRecoveryController extends Controller
{
    // Paso 1: buscar el correo en usuarios y crear una solicitud de recuperación.
    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $usuario = Usuario::where('email', $data['email'])
            ->with('credenciales')
            ->first();

        if ($usuario === null || $usuario->credenciales === null) {
            return response()->json([
                'message' => 'No existe un usuario con ese correo electrónico',
            ], 404);
        }

        $token = Str::random(64);

        DB::transaction(function () use ($usuario, $token): void {
            DB::table('rec_password')
                ->where('id_usuario', $usuario->id_usuario)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'cancelado']);

            DB::table('rec_password')->insert([
                'id_usuario' => $usuario->id_usuario,
                'codigo' => Hash::make($token),
                'estado' => 'pendiente',
                'fecha_solicitud' => now(),
                'fecha_expiracion' => now()->addMinutes(60),
            ]);
        });

        // Con MAIL_MAILER=log, este enlace queda disponible en storage/logs/laravel.log.
        Log::info('Enlace de recuperación de contraseña generado', [
            'email' => $usuario->email,
            'url' => url('/api/auth/reset-password?email='.urlencode($usuario->email).'&token='.$token),
        ]);

        return response()->json([
            'message' => 'Solicitud de recuperación creada',
        ]);
    }

    // Paso 2: validar el token pendiente y actualizar la contraseña.
    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $usuario = Usuario::where('email', $data['email'])
            ->with('credenciales')
            ->first();

        $solicitud = $usuario === null
            ? null
            : DB::table('rec_password')
                ->where('id_usuario', $usuario->id_usuario)
                ->where('estado', 'pendiente')
                ->where('fecha_expiracion', '>', now())
                ->latest('id_solicitud')
                ->first();

        if (
            $usuario === null
            || $usuario->credenciales === null
            || $solicitud === null
            || ! Hash::check($data['token'], $solicitud->codigo)
        ) {
            return response()->json([
                'message' => 'El token de recuperación no es válido o expiró',
            ], 400);
        }

        DB::transaction(function () use ($data, $solicitud, $usuario): void {
            /** @var User $credenciales */
            $credenciales = $usuario->credenciales;
            $credenciales->password = $data['password'];
            $credenciales->save();

            DB::table('rec_password')
                ->where('id_solicitud', $solicitud->id_solicitud)
                ->update([
                    'estado' => 'utilizado',
                ]);
        });

        return response()->json([
            'message' => 'Contraseña actualizada correctamente',
        ]);
    }
}
