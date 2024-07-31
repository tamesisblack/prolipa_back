<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudianteMatriculado extends Model
{
    use HasFactory;
    protected $table = "mat_estudiantes_matriculados";
    protected $primaryKey = 'id_matricula';
    protected $fillable = [
        'id_estudiante',
        'id_periodo',
        'fecha_matricula',
        'imagen',
        'url',
        'nivel',
        'paralelo',
        'observacion',
        'estado_matricula',
    ];
    public $timestamps = false;
}
