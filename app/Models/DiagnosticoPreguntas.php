<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosticoPreguntas extends Model
{
    use HasFactory;
    protected $table = "diagnostico_preguntas";
    protected $primaryKey = "id";
}
