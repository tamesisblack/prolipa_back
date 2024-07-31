<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificacionHistorico extends Model
{
    use HasFactory;
    protected $table  ="verificaciones_detalleventa_historico";

    protected $primaryKey = 'id';

    protected $fillable = [
        'vencodigo',
        'procodigo',
        'tipo',
        'accion',
        'numverificacion',
        'cantidadanterior',
        'cantidadactual'
    ];
}
