<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Remision extends Model
{
    use HasFactory;
    protected $table  ='remision_copy';

    protected $primaryKey = 'remi_codigo';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'remi_motivo',
        'remi_dir_partida', 
        'remi_destinatario',
        'remi_ruc_destinatario',
        'remi_direccion',
        'remi_nombre_transportista',
        'remi_ci_transportista',
        'remi_detalle',
        'remi_cantidad',
        'remi_unidad_medida',
        'remi_num_factura',
        'remi_fecha_inicio',
        'remi_fecha_final',
        'trans_codigo',
        'remi_guia_remision',
        'remi_obs',
        'remi_responsable',
        'remi_paquete',
        'remi_funda',
        'remi_rollo',
        'remi_flete',
        'remi_pagado',
        'remi_estado',
        'remi_idempresa',
        'created_at',
        'updated_at',
        'user_created',
    ];
}
