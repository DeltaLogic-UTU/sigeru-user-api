<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;



    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $table = 'credenciales'; 
    protected $primaryKey = 'id_credenciales'; 
    public $timestamps = false;
    protected $fillable = [
        'id_usuario',
        'cedula',
        'password',
        'ultimo_login',
        'token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
    /** 
     * datos guardados dentro del payload del JWT
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'id_usuario' => $this->id_usuario,
            'cedula' => $this->cedula,
        ];
    }
    /**
     * Relación con tabla principal Usuarios
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

}
