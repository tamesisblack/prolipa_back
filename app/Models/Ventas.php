<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ventas extends Model
{
    use HasFactory;
    protected $table = "f_venta";
    public $timestamps = false;
    protected $primaryKey = 'ven_codigo';
    public $incrementing = false;
    protected $fillable = [
        'ven_codigo',
        'tip_ven_codigo',
        'est_ven_codigo',
        'ven_tipo_inst',
        'ven_comision',
        'ven_valor',
        'ven_pagado',
        'ven_com_porcentaje',
        'ven_iva_por',
        'ven_iva',
        'ven_desc_por',
        'ven_descuento',
        'ven_fecha',
        'ven_idproforma',
        'ven_transporte',
        'ven_devolucion',
        'ven_remision',
        'ven_fech_remision',
        'institucion_id',
        'periodo_id',
        'updated_at',
        'user_created',
        'id_empresa',
        'id_ins_depacho',
        'id_sucursal',
        'idtipodoc',
        'ven_subtotal',
        'impresion',
        'ven_observacion',
        'ven_p_libros_obsequios',
        'ven_cliente',
        'estadoPerseo',
        'idPerseo',
        'proformas_codigo',
        'fecha_envio_perseo',
        'clientesidPerseo',
        'id_factura',
        'ruc_cliente',
        'anuladoEnPerseo',
        'doc_intercambio',
        'user_intercambio',
        'fecha_intercambio',
        'observacion_intercambio',
        'user_anulado',
        'fecha_anulacion',
        'observacionAnulacion',
        'id_pedido',
        'fecha_notaCredito',
        'fecha_sri',
        'documento_sri',
        'fecha_update_sri',
        'usuario_update_sri',
        'documento_ref_nci',
    ];

}
