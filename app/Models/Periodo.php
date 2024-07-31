<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    protected $table = "periodoescolar";
    protected $fillable =["fecha_inicial",
    "fecha_final",
    "region_idregion",
    "descripcion",
    "periodoescolar",
    "finicio_limite",
    "fhasta_limite"
    ];
    protected $primaryKey = 'idperiodoescolar';
	public $timestamps = false;
}
