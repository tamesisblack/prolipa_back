<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocenteFormularioAsignaturas extends Model
{
    use HasFactory;
    protected $table = "docente_formulario_asignaturas";
    protected $primaryKey = 'id';
}
