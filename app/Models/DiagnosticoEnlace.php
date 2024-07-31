<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiagnosticoEnlace extends Model
{
    use HasFactory;
    protected $table = "diagnostico_enlace";
    protected $primaryKey = "id";
}
