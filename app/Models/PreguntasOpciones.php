<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreguntasOpciones extends Model
{
    use HasFactory;
    protected $table = 'opciones_preguntas';
    protected $primaryKey = 'id_opcion_pregunta';
}
