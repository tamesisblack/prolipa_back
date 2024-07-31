<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncuestaRespuestaDetalles extends Model
{
    use HasFactory;
    protected $table = "encuesta_respuesta_detalles";
    protected $primaryKey = "id";
}
