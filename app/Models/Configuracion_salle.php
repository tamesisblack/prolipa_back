<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion_salle extends Model
{
    protected $table = 'salle_configuracion';
    protected $primaryKey = 'id_configuracion';
    protected $fillable = [
        'id_configuracion', 'tiempo_evaluacion', 'fecha_inicio', 'fecha_fin', 'ver_respuestas', 'observaciones', 'created_at', 'updated_at'
    ];
}
