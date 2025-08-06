<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// IMPORTANTE: agregar esta línea para Sanctum
use Laravel\Sanctum\HasApiTokens;

use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable
{
    // Agrega HasApiTokens aquí
    use HasApiTokens, HasFactory, Notifiable, \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'usuario';
    protected $primaryKey = 'idusuario';

    protected $fillable = [
        'nombres', 'apellidos', 'name_usuario', 'email', 'password', 'cedula', 'id_group', 'remember_token', 'session_id', 'estado_idEstado', 'capacitador'
    ];

    protected $rememberTokenName = false;

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'id_group', 'id');
    }

    public function institucion()
    {
        return $this->belongsTo(Institucion::class, 'institucion_idInstitucion', 'idInstitucion');
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
