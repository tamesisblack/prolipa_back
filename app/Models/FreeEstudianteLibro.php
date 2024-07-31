<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreeEstudianteLibro extends Model
{
    use HasFactory;
    protected $table = "free_estudiante_libro";
    protected $primaryKey = 'id';
    protected $fillable = [
        'institucion_id',
        'periodo_id',
        'anio',
        'nivel_id',
        'libro_id',
        'serie_id',

    ];
}
