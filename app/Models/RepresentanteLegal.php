<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepresentanteLegal extends Model
{
    use HasFactory;
    protected $table = "mat_representante_legal";
    protected $primaryKey = 'rep_legal_id';
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
        'email_institucional'
       

    ];
}
