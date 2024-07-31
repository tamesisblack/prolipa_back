<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProyectoRespuesta extends Model
{
    use HasFactory;
    protected $table = "proyecto_respuesta";
    protected $primaryKey = 'id';
    protected $fillable = [
        'proyecto_id',
        'idusuario',
        'introduccion',
        'tarea',
        'proceso',
        'recurso',
        'evaluacion',
        'conclusion',
        'calificacion',
        'comentario_docente',
        'curso',
    ];
}
