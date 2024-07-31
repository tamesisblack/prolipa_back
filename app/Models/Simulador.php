<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simulador extends Model
{
    use HasFactory;

    protected $table = "simulador";
    protected $primaryKey = 'simulador_id';
    protected $fillable = [
        'asignatura_id',
        'nombre',
        'descripcion',
        'link',
        'link_tutorial',
        'estado',
    ];
}
