<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;
    
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'estado',
        'tipo',
        'fecha_registro',
    ];
    
    public function credenciales()
    {
        return $this->hasOne(User::class, 'id_usuario', 'id_usuario');
    }
}
