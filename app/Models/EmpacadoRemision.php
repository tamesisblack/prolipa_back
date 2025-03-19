<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpacadoRemision extends Model
{
    use HasFactory;
    protected $table = "empacado_remision";
    protected $fillable = [
        'remi_codigo',
        'remi_idempresa',
        'remi_motivo',
        'remi_dir_partida',
        'remi_destinatario',
        'remi_ruc_destinatario',
        'remi_direccion',
        'remi_nombre_transportista',
        'remi_ci_transportista',
        'remi_detalle',
        'remi_num_factura',
        'remi_fecha_inicio',
        'trans_codigo',
        'remi_obs',
        'remi_responsable',
        'remi_carton',
        'remi_paquete',
        'remi_funda',
        'remi_rollo',
        'remi_flete',
        'remi_pagado',
        'user_created',
    ];
}
