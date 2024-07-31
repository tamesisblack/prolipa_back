<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeguimientoInstitucionTemporal extends Model
{
    use HasFactory;
    protected $table = "seguimiento_institucion_temporal";
    protected $primaryKey = 'institucion_temporal_id';
    protected $fillable = [
        'nombre_institucion',
        'periodo_id',
        'asesor_id',
        'region',
        'ciudad',
        'estado',
        'usuario_editor',
       
    ];

}
