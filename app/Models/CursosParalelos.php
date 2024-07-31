<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CursosParalelos extends Model
{
    use HasFactory;
    protected $table = "mat_cursos_paralelos";
    protected $primaryKey = 'cursos_paralelos_id';
    protected $fillable = [
        'nivelInstitucion_id',
        'nivel_id',
        'paralelo_id',
        'institucion_id',
        'periodo_id',
    ];
    public $timestamps = false;
}
