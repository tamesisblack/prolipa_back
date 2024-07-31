<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planificacion extends Model
{
    protected $table = "planificacion";
    protected $primaryKey = 'idplanificacion';
    protected $fillable = [
        'nombreplanificacion',
        'descripcionplanificacion',
        'webplanificacion',
        'asignatura_idasignatura',
        'user_created',
        'Estado_idEstado',
    ];
	public $timestamps = true;
}
