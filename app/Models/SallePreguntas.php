<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SallePreguntas extends Model
{
    protected $table = "salle_preguntas";
    protected $primaryKey = 'id_pregunta';

    // Relación con SalleAsignaturas
    public function asignatura()
    {
        return $this->belongsTo(SalleAsignaturas::class, 'id_asignatura', 'id_asignatura');
    }

    // Relación con SallePreguntasOpcion
    public function opciones()
    {
        return $this->hasMany(SallePreguntasOpcion::class, 'id_pregunta', 'id_pregunta');
    }

    // Relación con SalleRespuestas
    public function respuestas()
    {
        return $this->hasMany(SalleRespuestas::class, 'id_pregunta', 'id_pregunta');
    }

    // Relación con SalleEvaluaciones a través de la tabla intermedia salle_preguntas_evaluacion
    public function evaluaciones()
    {
        return $this->belongsToMany(SalleEvaluaciones::class, 'salle_preguntas_evaluacion', 'id_pregunta', 'id_evaluacion');
    }
    // Relación con TiposPreguntas
    public function tipo()
    {
        return $this->belongsTo(TiposPreguntas::class, 'id_tipo_pregunta', 'id_tipo_pregunta');
    }
}
