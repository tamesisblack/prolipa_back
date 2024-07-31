<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TareaEstudiante extends Model
{
    use HasFactory;
    protected $table        = "tarea";
    protected $primaryKey   = "idtarea";
}
