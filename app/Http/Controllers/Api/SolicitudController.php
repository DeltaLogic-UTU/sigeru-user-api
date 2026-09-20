<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudAcceso;
use App\Models\User;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SolicitudController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'cedula' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['required', 'string', 'max:30'],
            'rol_solicitado' => ['required', 'string', 'in:administrador,usuario,chofer,barrendero,operario,cuadrilla'],
            'motivo' => ['nullable', 'string'],
        ]);

        $solicitud = SolicitudAcceso::create([
            ...$data,
            'estado' => 'pendiente',
            'fecha_solicitud' => now(),
        ]);

        return response()->json([
            'message' => 'Solicitud de acceso creada exitosamente',
            'data' => $solicitud,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = SolicitudAcceso::query();

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado')->toString());
        }

        return response()->json([
            'data' => $query->orderByDesc('fecha_solicitud')->get(),
        ]);
    }

    public function aprobar(Request $request, int $id_solicitud): JsonResponse
    {
        $solicitud = SolicitudAcceso::findOrFail($id_solicitud);

        if ($solicitud->estado !== 'pendiente') {
            return response()->json([
                'message' => 'La solicitud ya ha sido procesada',
            ], 400);
        }

        $admin = auth('api')->user();

        DB::transaction(function () use ($solicitud, $admin): void {
            $usuario = Usuario::create([
                'nombre' => $solicitud->nombre,
                'apellido' => $solicitud->apellido,
                'email' => $solicitud->email,
                'telefono' => $solicitud->telefono,
                'estado' => 'activo',
                'tipo' => $solicitud->rol_solicitado,
                'fecha_registro' => now(),
            ]);

            User::create([
                'id_usuario' => $usuario->id_usuario,
                'cedula' => $solicitud->cedula,
                'password' => Hash::make('defaultPassword123'),
            ]);

            $solicitud->update([
                'estado' => 'aprobada',
                'fecha_resolucion' => now(),
                'id_admin_resuelve' => $admin->usuario->id_usuario,
            ]);
        });

        return response()->json([
            'message' => 'Solicitud aprobada exitosamente',
            'data' => $solicitud->fresh(),
        ]);
    }

    public function rechazar(Request $request, int $id_solicitud): JsonResponse
    {
        $solicitud = SolicitudAcceso::findOrFail($id_solicitud);

        if ($solicitud->estado !== 'pendiente') {
            return response()->json([
                'message' => 'La solicitud ya ha sido procesada',
            ], 400);
        }

        $solicitud->update([
            'estado' => 'rechazada',
            'fecha_resolucion' => now(),
            'id_admin_resuelve' => auth('api')->user()->usuario->id_usuario,
        ]);

        return response()->json([
            'message' => 'Solicitud rechazada exitosamente',
            'data' => $solicitud->fresh(),
        ]);
    }
}
