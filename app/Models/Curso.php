<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
class Curso extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = "curso";
    protected $primaryKey = 'idcurso';
    protected $fillable = [
        'nombre',
        'seccion',
        'materia',
        'aula',
        'codigo',
        'idusuario',
        'id_asignatura',
        'id_periodo',
    ];
	public $timestamps = false;
}
