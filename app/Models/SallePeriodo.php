<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SallePeriodo extends Model
{
    use HasFactory;
    protected $table        = "salle_periodos_evaluacion";
    protected $primaryKey   = "id";
}
