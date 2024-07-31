<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleCompraOrden extends Model
{
    use HasFactory;
    protected $table = '1_4_cal_detalle_compra';
	
    protected $primaryKey = 'det_com_codigo';
    public $timestamps = false;

    protected $fillable = [
        'det_com_codigo',
        'com_codigo',
        'pro_codigo', 
        'det_com_cantidad',
        'det_com_factura',
        'det_com_nota',
        'det_com_valor_u', 
    ];

}
