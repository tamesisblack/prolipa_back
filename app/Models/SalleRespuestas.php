<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalleRespuestas extends Model
{
    use HasFactory;
    protected $table        = "salle_respuestas_preguntas";
    protected $primaryKey   = "id_respuesta_pregunta";
}
