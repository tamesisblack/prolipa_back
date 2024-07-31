<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecursosColegio extends Model
{
    use HasFactory;

    
    protected $table = "recursos_for_Colegio";
    protected $primaryKey = 'id';
    protected $fillable = [
        'recurso_id',
        'nombre_recurso',
        'tipo_recurso',
        'idasignatura',
        'nombre_asignatura',
        'id_unidad',
        'nombre_unidad',
        'estado',
        
    ];
}
