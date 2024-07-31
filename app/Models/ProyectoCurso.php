<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProyectoCurso extends Model
{
    use HasFactory;
    protected $table = "proyecto_curso";
    protected $primaryKey = 'id';
    protected $fillable = [
        'proyecto_id',
        'curso',
        'estado',
        'idusuario',
        'asignatura_id'
    ];
}
