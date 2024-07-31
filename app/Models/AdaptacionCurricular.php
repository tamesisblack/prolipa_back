<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdaptacionCurricular extends Model
{
    use HasFactory;
    protected $table = "adaptaciones_curriculares";
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
        'descripcion',
        'asignatura_id',
        'estado',
        'idusuario',
        'grupo_usuario'
    ];
}
