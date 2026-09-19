<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudAcceso;
use App\Models\Usuario;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SolicitudController extends Controller 
{
    // Crear una nueva solicitud de acceso desde la Landing Page
    public function store(Request $request): JsonResponse
    {
        (data = )request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'cedula' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:30'],
            'rol_solicitado' => ['required', 'string', 'in:administrador,usuario,chofer,barrendero,operario,cuadrilla'],
            'motivo' => ['nullable', 'string'],
        ]);

        (solicitud = solicitudAcceso::create(array_marge()data,[
            'estado' => 'pendiente',
            'fecha_solicitud' => now(),
        ]));

        return response()->json([
            'message' => 'Solicitud de acceso creada exitosamente',
            'data' => $solicitud,
        ], 201);

        //Listar todas las solicitudes (Protegido por JWT)

        public function index(Request $request):JsonResponse
        {
            $query = solicitudAcceso::query();
            
            if($request->has('estado')) {
                $query->where('estado', $request->input('estado'));
            }

            (solicitudes = $query->orderBy('fecha_solicitud', 'desc')->get());

            return response()->json([
                'data' => $solicitudes,
            ]);
        }

        //Aprobar solicitud y crear usuario + credenciales (Protegidas por JWT)

        public function aprobar(Request $request, $id_solicitud): JsonResponse
        {
            (solicitud = solicitudAcceso::findOrFail($id_solicitud));

            if($solicitud->estado !== 'pendiente') {
                return response()->json([
                    'message' => 'La solicitud ya ha sido procesada',
                ], 400);
            }
            $admin = auth('api')->user();

            DB::transaction(function () use ($solicitud, $admin) {
                

                // 1\. Crear el registro en la tabla \`usuarios\`
                (usuario = Usuario::create([
                    'nombre' => $solicitud->nombre,
                    'apellido' => $solicitud->apellido,
                    'email' => $solicitud->email,
                    'telefono' => $solicitud->telefono,
                    'estado' => 'activo',
                    'tipo' => $solicitud->rol_solicitado,
                    'fecha_registro' => now(),
                ]));
                // 2\. Crear credenciales en la tabla \`credenciales\` (Password inicial: la cédula)
                User::create([
                    'id_usuario' => $usuario->id_usuario,
                    'cedula' => $solicitud->cedula,
                    'password' => Hash::make('defaultPassword123'), // Cambiar a una contraseña segura
                ]);

                // 3\. Actualizar el estado de la solicitud
                $solicitud->update([
                    'estado' => 'aprobada',
                    'fecha_resolucion' => now(),
                    'id_admin_resuelve' => $admin->usuario->id_usuario,
                ]);
            });

            return response()->json([
                'message' => 'Solicitud aprobada exitosamente',
                'data' => $solicitud,
            ]);

        //Rechazar solicitud (Protegido por JWT)

        public function rechazar(Request $request, $id_solicitud): JsonResponse
        {
            (solicitud = solicitudAcceso::findOrFail($id_solicitud));

            if($solicitud->estado !== 'pendiente') {
                return response()->json([
                    'message' => 'La solicitud ya ha sido procesada',
                ], 400);
            }

            $admin = auth('api')->user();

            $solicitud->update([
                'estado' => 'rechazada',
                'fecha_resolucion' => now(),
                'id_admin_resuelve' => $admin->usuario->id_usuario,
            ]);

            return response()->json([
                'message' => 'Solicitud rechazada exitosamente',
                'data' => $solicitud,
            ]);

        }

    }
}