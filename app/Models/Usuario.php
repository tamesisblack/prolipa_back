<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Usuario extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = "usuario";
    protected $primaryKey = 'idusuario';
    public $timestamps = true;
    protected $fillable = [
        //'nombres', 'apellidos', 'name_usuario', 'email', 'password','cedula','id_group','remember_token','session_id'
        'cedula',
        'nombres',
        'apellidos',
        'name_usuario',
        'email',
        'date_created',
        'id_group',
        'p_ingreso',
        'institucion_idInstitucion',
        'estado_idEstado',
        'idcreadorusuario',
        'modificado_por',
        'password_status',
        'foto_user',
        'telefono',
        'password',
        'sexo',
        'nacionalidad',
        'fecha_nacimiento',
        'seccion',
        'curso',
        'paralelo',
        'retirado',
        'update_datos',
        'cargo_id',
        'iniciales',
        'capacitador',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function seminarios()
    {
        return $this->belongsToMany(Seminarios::class, 'seminarios_capacitador', 'idusuario', 'seminario_id');
    }
}
