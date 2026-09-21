<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudAcceso extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_acceso';

    protected $primaryKey = 'id_solicitud';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'cedula',
        'email',
        'telefono',
        'rol_solicitado',
        'estado',
        'fecha_solicitud',
        'fecha_resolucion',
        'id_admin_resuelve',
    ];

    // Relación con el administrador que resuelve
    public function admin()
    {
        return $this->belongsTo(Usuario::class, 'id_admin_resuelve', 'id_usuario');
    }
}
