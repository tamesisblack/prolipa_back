<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleVentasF extends Model
{
    use HasFactory;
    protected $table  ='f_detalle_venta_agrupado';

    protected $primaryKey = 'det_ven_codigo';
    protected $fillable = [
        'det_ven_codigo',
        'id_factura',
        'id_empresa',
        'pro_codigo',
        'det_ven_cantidad',
        'det_ven_valor_u'
    ];
	public $timestamps = false;
}
