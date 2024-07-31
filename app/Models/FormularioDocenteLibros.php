<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormularioDocenteLibros extends Model
{
    use HasFactory;
    protected $table = "docentes_formulario_libros";
    protected $primaryKey = 'id';
    protected $fillable = [
        'serie','docente_formulario_id','libro_id','cursos'
    ];
}
