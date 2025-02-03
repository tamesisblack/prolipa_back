<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SallePreguntasEvaluacion extends Model
{
    use HasFactory;
    protected $table = "salle_preguntas_evaluacion";
    protected $primaryKey = 'id_pregunta_eval';
}
