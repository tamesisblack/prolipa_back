<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitucionFueraProlipa extends Model
{
    use HasFactory;
    protected $table = "institucion_fuera_prolipa";
    protected $primaryKey = 'id';
    protected $fillable = [
        'institucion_id',
        'institucion_id_temporal',
        'nombre_institucion_temporal',
        'estado_institucion_temporal',
        'nombre_editorial',
        'asesor_id',
        'periodo_id',
        'estado', 
        'ciudad_id',
        'estado_idEstado',
        'asesor_planificacion_id'
    ];
}
