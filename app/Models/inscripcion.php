<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class inscripcion extends Model
{
    protected $table = "inscripcion";
    protected $primaryKey = 'idseminario';
    protected $fillable = [
        'cedula',
        'nombres',
        'apellidos',
        'celular',
        'correo',
        'idciudad',
        'idinstitucion',
        'seminario_idseminario',
        'idnivel',
        'asignatura',
        'institucion'
    ];
	public $timestamps = false;
}
