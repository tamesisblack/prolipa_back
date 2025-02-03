<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiposPreguntas extends Model
{
    use HasFactory;
    protected $table        = "tipos_preguntas";
    protected $primaryKey   = "id_tipo_pregunta";
}
