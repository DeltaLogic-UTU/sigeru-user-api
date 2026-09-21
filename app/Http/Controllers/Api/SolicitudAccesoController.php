<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudAcceso;
use App\Models\User;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudAccesoController extends Controller
{
    // Guardar nueva solicitud (Landing Page)
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'cedula' => 'nullable|string|max:30',
            'email' => 'required|email|max:150|unique:solicitudes_acceso,email',
            'telefono' => 'nullable|string|max:30',
            'rol_solicitado' => 'required|in:administrador,chofer,barrendero,operario,vecino',
        ]);

        $solicitud = SolicitudAcceso::create($data);

        return response()->json([
            'message' => 'Solicitud enviada correctamente',
            'data' => $solicitud,
        ], 201);
    }

    // Listar solicitudes pendientes (Admin)
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SolicitudAcceso::where('estado', 'pendiente')->get(),
        ]);
    }

    // Aprobar o rechazar solicitud
    public function resolver(Request $request, $id): JsonResponse
    {
        $solicitud = SolicitudAcceso::findOrFail($id);

        $data = $request->validate([
            'estado' => 'required|in:aprobado,rechazado',
        ]);

        if ($solicitud->estado !== 'pendiente') {
            return response()->json([
                'message' => 'La solicitud ya fue resuelta',
            ], 422);
        }

        if ($data['estado'] === 'aprobado' && blank($solicitud->cedula)) {
            return response()->json([
                'message' => 'La solicitud necesita una cédula para crear el usuario',
            ], 422);
        }

        if ($data['estado'] === 'aprobado' && (
            User::where('cedula', $solicitud->cedula)->exists()
            || Usuario::where('email', $solicitud->email)->exists()
        )) {
            return response()->json([
                'message' => 'Ya existe un usuario con esa cédula o correo electrónico',
            ], 422);
        }

        $usuario = DB::transaction(function () use ($data, $solicitud): Usuario {
            $idAdmin = auth('api')->user()?->id_usuario;

            $solicitud->estado = $data['estado'];
            $solicitud->fecha_resolucion = now();
            $solicitud->id_admin_resuelve = $idAdmin;
            $solicitud->save();

            if ($data['estado'] !== 'aprobado') {
                return new Usuario;
            }

            // Al aprobar, se crean el perfil y sus credenciales dentro de una sola transacción.
            $usuario = Usuario::create([
                'nombre' => $solicitud->nombre,
                'apellido' => $solicitud->apellido,
                'email' => $solicitud->email,
                'telefono' => $solicitud->telefono,
                'estado' => 'disponible',
                'tipo' => $solicitud->rol_solicitado,
            ]);

            // El modelo User aplica automáticamente el hash mediante su cast de password.
            User::create([
                'id_usuario' => $usuario->id_usuario,
                'cedula' => $solicitud->cedula,
                'password' => 'Sigeru123',
            ]);

            return $usuario;
        });

        $response = [
            'message' => "Solicitud {$data['estado']}",
            'data' => $solicitud,
        ];

        if ($data['estado'] === 'aprobado') {
            $response['usuario'] = $usuario;
            $response['password_inicial'] = 'Sigeru123';
        }

        return response()->json($response);
    }
}
