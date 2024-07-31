<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalleEvaluaciones extends Model
{
    use HasFactory;

    protected $table = "salle_evaluaciones";
    protected $primaryKey = 'id_evaluacion';
}
