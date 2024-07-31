<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    use HasFactory;
    protected $table = "proyectos";
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
        'descripcion',
        'archivo_docente',
        'url_docente',
        'archivo_estudiante',
        'url_estudiante',
        'estado',
        'idusuario',
    ];

}
