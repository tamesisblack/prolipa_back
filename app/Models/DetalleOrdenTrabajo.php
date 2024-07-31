<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleOrdenTrabajo extends Model
{
    use HasFactory;
     protected $table  ='1_1_detalle_orden_trabajo';

    protected $primaryKey = 'det_or_codigo';
    public $timestamps = false;

    protected $fillable = [
        'det_or_codigo',
        'or_codigo',
        'pro_codigo',
        'det_or_cantidad', 
        'det_or_tamaño',
        'det_or_int_paginas',
        'det_or_in_codigo',
        'det_or_in_tintas',
        'mat_cub_codigo',
        'det_or_cub_tintas',
        'det_or_acabados',
        'det_or_posible_entrega',
        'det_or_observaciones',
        'det_or_recubrimiento',
        'det_or_guias',
        'det_or_planificacion',

        
    ];
}
