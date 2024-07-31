<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignatura extends Model
{
    protected $table = "asignatura";
    protected $primaryKey = 'idasignatura';
    protected $fillable = [
        'nombreasignatura',
        'area_idarea',
        'nivel_idnivel',
        'tipo_asignatura',
        'estado'
    ];
	public $timestamps = false;
}
