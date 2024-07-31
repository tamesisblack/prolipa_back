<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seminario extends Model
{
    protected $table = "seminario";
    protected $primaryKey = 'idseminario';
    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'hora_inicio',
        'link_presentacion',
        'cantidad_participantes',
        'link_registro',
        'idcurso',
    ];
	public $timestamps = false;
}
