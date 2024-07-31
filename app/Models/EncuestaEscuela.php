<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncuestaEscuela extends Model
{
    use HasFactory;
    protected $table = "encuestas_escuela";
    protected $primaryKey = "id";
}
