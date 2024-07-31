<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropuestaMetologica extends Model
{
    use HasFactory;
    protected $table = "propuesta_metodologicas";
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre',
        'descripcion',
        'asignatura_id',
        'estado',
        'idusuario'
    ];
}
