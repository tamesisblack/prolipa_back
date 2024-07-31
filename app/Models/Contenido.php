<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contenido extends Model
{
    protected $table = "contenido";
    protected $primaryKey = 'idcontenido';
    protected $fillable = [
        'nombre',
        'url',
        'file_ext',
        'unidad',
        'curso_idcurso',
        'idasignatura',
        'temas'
    ];
}
