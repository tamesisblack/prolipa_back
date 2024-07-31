<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncuestaPreguntas extends Model
{
    use HasFactory;
    protected $table = "encuesta_opciones";
    protected $primaryKey = "id";
}
