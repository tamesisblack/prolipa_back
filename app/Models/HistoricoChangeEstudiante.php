<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoChangeEstudiante extends Model
{
    use HasFactory;
    protected $table      = "his_change_estudiantes";
    protected $primaryKey = "id";
}
