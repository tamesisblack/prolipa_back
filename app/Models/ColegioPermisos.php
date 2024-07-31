<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColegioPermisos extends Model
{
    use HasFactory;
    protected $table = "colegio_permisos";
    protected $primaryKey = 'id';
    protected $fillable = [
        'institucion_id',
        'asignatura_id',
        'permisos_acordeon',
        'permisos_libros',
        'permisos_cursos',
        'permisos_cuadernos',
        'permisos_planificaciones', 
    ];
}
