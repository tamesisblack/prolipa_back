<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosticoPreguntasDetalle extends Model
{
    use HasFactory;
    protected $table = "diagnostico_preguntas_detalle";
    protected $primaryKey = "id";
}
