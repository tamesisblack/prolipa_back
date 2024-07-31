<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documentoliq extends Model
{
    use HasFactory;
    protected $table  ='1_4_documento_liq';

    protected $primaryKey = 'doc_codigo';
    public $timestamps = false;

    protected $fillable = [
        'doc_codigo',
        'doc_valor',
        'doc_numero',
        'doc_nombre',
        'doc_apellidos',
        'doc_ci',
        'doc_ruc',
        'doc_cuenta',
        'doc_institucion',
        'doc_tipo',
        'doc_observacion',
        'ven_codigo',
        'doc_fecha',
        'user_created',
        'distribuidor_temporada_id',
        'tip_pag_codigo',
        'tipo_aplicar',
        'calculo',
        'unicoEvidencia',
        'archivo',
        'url',
        'verificaciones_pagos_detalles_id',
        'estado',
        'institucion_id',
        'estado_contrato',
        'created_at',
        'updated_at',

    ];

}
