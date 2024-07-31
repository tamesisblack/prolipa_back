<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepresentanteEconomico extends Model
{
    use HasFactory;
    protected $table = "mat_representante_economico";
    protected $primaryKey = 'rep_economico_id';
	public $timestamps = true;
    protected $fillable = [
        //'nombres', 'apellidos', 'name_usuario', 'email', 'password','cedula','id_group','remember_token','session_id'
        'c_estudiante',
        'parentesco',
        'cedula',
        'apellidos',
        'nombres',
        'email',
        'sexo',
        'nacionalidad',
        'telefono_casa',
        'telefono_celular',
        'direccion',
        'vive_con',
        'puede_retirar',
        'profesion',
        'empresa',
        'direccion_trabajo',
       

    ];
}
