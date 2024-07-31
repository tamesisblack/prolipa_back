<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentasF extends Model
{
    use HasFactory;
    protected $table = "f_venta_agrupado";
    public $timestamps = false;
    protected $primaryKey = 'id_factura';
    public $incrementing = false;
    protected $fillable = [
        'id_factura',      
        'ven_desc_por',  
        'ven_iva_por',   
        'ven_descuento',
        'ven_iva',
        'ven_transporte',
        'ven_valor',
        'ven_subtotal',
        'ven_devolucion',
        'ven_pagado',
        'id_ins_depacho',
        'periodo_id',
        'idtipodoc',
        'ven_cliente',
        'clienteidPerseo',
        'ven_fecha',
        'user_created',
        'updated_at'
    ];
}
