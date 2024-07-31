<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CursoLibro extends Model
{
    protected $table = "libro_has_curso";
    protected $primaryKey = 'id_libro_has_curso';
    protected $fillable = [
        'libro_idlibro',
        'curso_idcurso',
    ];
	public $timestamps = false;
}
